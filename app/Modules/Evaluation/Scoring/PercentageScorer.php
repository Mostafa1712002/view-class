<?php

namespace App\Modules\Evaluation\Scoring;

use App\Models\Evaluation;
use App\Modules\Evaluation\Scoring\Concerns\ReadsSnapshot;

/**
 * Percentage scoring (Trello #200).
 *
 * The evaluator enters, per item, a 0–100 percentage of how far the item is met
 * (stored on the single per-item response's `score`). Each item carries an
 * admin-set `weight` which is its share of the final evaluation. The item's
 * contribution is the entered percentage scaled by that weight:
 *
 *     item earned = item.weight × (enteredPercentage / 100)
 *     total       = Σ earned
 *     max         = Σ weights  (= 100 for a valid form)
 *     percentage  = total / max × 100
 *
 * Example: weight 5%, evaluator enters 80% → earned = 5 × 0.80 = 4% of the final.
 *
 * A missing response contributes 0 (partial grace). Entered values are clamped
 * to [0, 100] defensively even though the response store layer validates them.
 *
 * This scorer is additive: the rubric / rating-scale / checklist scorers are
 * untouched, so every existing evaluation scores identically.
 */
final class PercentageScorer implements ScoringStrategy
{
    use ReadsSnapshot;

    public function score(Evaluation $evaluation, array $snapshotPayload): ScoreResult
    {
        $responses = $this->responsesByItem($evaluation);

        $total     = 0.0;
        $max       = 0.0;
        $breakdown = [];

        foreach ($this->activeItems($snapshotPayload) as $item) {
            $itemId = (int) $item['id'];
            $weight = (float) ($item['weight'] ?? 0);
            $max   += $weight;

            $entered = null;
            $response = $responses[$itemId] ?? null;
            if ($response !== null && $response->score !== null) {
                $entered = max(0.0, min(100.0, (float) $response->score));
            }

            $earned = round($weight * (($entered ?? 0.0) / 100), 2);
            $total += $earned;

            $breakdown[] = [
                'item_id'            => $itemId,
                'item_name'          => $item['name'] ?? null,
                'weight'             => round($weight, 2),
                'earned'             => $earned,
                'max'                => round($weight, 2),
                // reproducibility input: the percentage the evaluator entered (out of 100)
                'entered_percentage' => $entered,
            ];
        }

        return ScoreResult::make($total, $max, $breakdown);
    }
}
