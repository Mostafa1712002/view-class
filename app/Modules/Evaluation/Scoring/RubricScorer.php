<?php

namespace App\Modules\Evaluation\Scoring;

use App\Models\Evaluation;
use App\Modules\Evaluation\Scoring\Concerns\ReadsSnapshot;

/**
 * Rubric scoring.
 *
 * The evaluator picks ONE level per item (a response with indicator_id = null
 * and a level_id). The item earns its full weight scaled by how strong the
 * chosen level is positionally:
 *
 *     item earned = item.weight × (levelRank / levelCount)
 *     total       = Σ earned
 *     max         = Σ weights  (= 100 for a valid form)
 *     percentage  = total / max × 100
 *
 * "levelRank" is the 1-based position of the chosen level among the form's
 * levels ordered by `sort_order` ascending. ASSUMPTION: levels are authored
 * weakest → strongest (lowest sort_order = weakest), so the strongest level
 * sits at the highest rank and earns full weight. This matches the spec's
 * "level N of M = (N/M) × weight".
 */
final class RubricScorer implements ScoringStrategy
{
    use ReadsSnapshot;

    public function score(Evaluation $evaluation, array $snapshotPayload): ScoreResult
    {
        $rankByLevelId = $this->levelRanks($snapshotPayload);
        $levelCount    = count($rankByLevelId);
        $responses     = $this->responsesByItem($evaluation);

        $total     = 0.0;
        $max       = 0.0;
        $breakdown = [];

        foreach ($this->activeItems($snapshotPayload) as $item) {
            $itemId = (int) $item['id'];
            $weight = (float) ($item['weight'] ?? 0);
            $max   += $weight;

            $chosenLevelId = null;
            $rank          = null;
            $earned        = 0.0;

            $response = $responses[$itemId] ?? null;
            if ($response !== null && $response->level_id !== null && $levelCount > 0) {
                $chosenLevelId = (int) $response->level_id;
                $rank          = $rankByLevelId[$chosenLevelId] ?? null;
                if ($rank !== null) {
                    $earned = $weight * ($rank / $levelCount);
                }
            }

            $earned  = round($earned, 2);
            $total  += $earned;

            $breakdown[] = [
                'item_id'         => $itemId,
                'item_name'       => $item['name'] ?? null,
                'weight'          => round($weight, 2),
                'earned'          => $earned,
                'max'             => round($weight, 2),
                // reproducibility inputs
                'chosen_level_id' => $chosenLevelId,
                'level_rank'      => $rank,
                'level_count'     => $levelCount,
            ];
        }

        return ScoreResult::make($total, $max, $breakdown);
    }

    /**
     * Map level_id → 1-based rank by sort_order ascending.
     *
     * @param  array<string,mixed> $payload
     * @return array<int,int>
     */
    private function levelRanks(array $payload): array
    {
        $levels = $payload['levels'] ?? [];
        $levels = is_array($levels) ? array_values(array_filter($levels, 'is_array')) : [];

        usort($levels, fn ($a, $b) => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));

        $ranks = [];
        foreach ($levels as $i => $level) {
            $ranks[(int) $level['id']] = $i + 1;
        }

        return $ranks;
    }
}
