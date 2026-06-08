<?php

namespace App\Modules\Evaluation\Scoring;

use App\Models\Evaluation;
use App\Modules\Evaluation\Scoring\Concerns\ReadsSnapshot;

/**
 * Rating-scale scoring.
 *
 * An item's indicators share the item weight equally. The evaluator rates each
 * indicator by picking a level; the level's numeric `value` (relative to the
 * highest level value on the form) determines how much of the indicator's share
 * is earned:
 *
 *     indicatorShare    = item.weight / indicatorCount
 *     indicator earned  = indicatorShare × (chosenLevelValue / maxLevelValue)
 *     item earned       = Σ indicator earned
 *     total             = Σ item earned
 *     max               = Σ weights  (= 100 for a valid form)
 *
 * An item with zero (active) indicators contributes 0 and never divides by zero.
 */
final class RatingScaleScorer implements ScoringStrategy
{
    use ReadsSnapshot;

    public function score(Evaluation $evaluation, array $snapshotPayload): ScoreResult
    {
        $valueByLevelId = $this->levelValues($snapshotPayload);
        $maxLevelValue  = $valueByLevelId === [] ? 0.0 : max($valueByLevelId);
        $responses      = $this->responsesByIndicator($evaluation);

        $total     = 0.0;
        $max       = 0.0;
        $breakdown = [];

        foreach ($this->activeItems($snapshotPayload) as $item) {
            $itemId     = (int) $item['id'];
            $weight     = (float) ($item['weight'] ?? 0);
            $max       += $weight;
            $indicators = $this->activeIndicators($item);
            $count      = count($indicators);
            $share      = $count > 0 ? $weight / $count : 0.0;

            $itemEarned       = 0.0;
            $indicatorDetails = [];

            foreach ($indicators as $indicator) {
                $indicatorId    = (int) $indicator['id'];
                $chosenLevelId  = null;
                $chosenValue    = null;
                $indEarned      = 0.0;

                $response = $responses[$indicatorId] ?? null;
                if ($response !== null && $response->level_id !== null && $maxLevelValue > 0) {
                    $chosenLevelId = (int) $response->level_id;
                    $chosenValue   = $valueByLevelId[$chosenLevelId] ?? null;
                    if ($chosenValue !== null) {
                        $indEarned = $share * ($chosenValue / $maxLevelValue);
                    }
                }

                $indEarned   = round($indEarned, 2);
                $itemEarned += $indEarned;

                $indicatorDetails[] = [
                    'indicator_id'    => $indicatorId,
                    'share'           => round($share, 2),
                    'earned'          => $indEarned,
                    'chosen_level_id' => $chosenLevelId,
                    'chosen_value'    => $chosenValue,
                    'max_value'       => $maxLevelValue,
                ];
            }

            $itemEarned = round($itemEarned, 2);
            $total     += $itemEarned;

            $breakdown[] = [
                'item_id'         => $itemId,
                'item_name'       => $item['name'] ?? null,
                'weight'          => round($weight, 2),
                'earned'          => $itemEarned,
                'max'             => round($weight, 2),
                // reproducibility inputs
                'indicator_count' => $count,
                'indicators'      => $indicatorDetails,
            ];
        }

        return ScoreResult::make($total, $max, $breakdown);
    }

    /**
     * Map level_id → numeric value.
     *
     * @param  array<string,mixed> $payload
     * @return array<int,float>
     */
    private function levelValues(array $payload): array
    {
        $levels = $payload['levels'] ?? [];
        $levels = is_array($levels) ? $levels : [];

        $out = [];
        foreach ($levels as $level) {
            if (is_array($level) && isset($level['id'])) {
                $out[(int) $level['id']] = (float) ($level['value'] ?? 0);
            }
        }

        return $out;
    }
}
