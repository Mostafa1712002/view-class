<?php

namespace App\Modules\Evaluation\Scoring;

use App\Models\Evaluation;
use App\Modules\Evaluation\Scoring\Concerns\ReadsSnapshot;

/**
 * Checklist scoring.
 *
 * No weights, no levels — every indicator is simply met / not-met
 * (response.checklist_value). The score is a coverage ratio:
 *
 *     total      = number of met indicators
 *     max        = total number of (active) indicators
 *     percentage = met / total × 100
 *
 * A missing response for an indicator counts as not-met (partial grace).
 * `max` of 0 (no indicators) yields percentage 0 with no division by zero.
 */
final class ChecklistScorer implements ScoringStrategy
{
    use ReadsSnapshot;

    public function score(Evaluation $evaluation, array $snapshotPayload): ScoreResult
    {
        $responses = $this->responsesByIndicator($evaluation);

        $totalMet    = 0;
        $totalCount  = 0;
        $breakdown   = [];

        foreach ($this->activeItems($snapshotPayload) as $item) {
            $itemId     = (int) $item['id'];
            $indicators = $this->activeIndicators($item);
            $itemMet    = 0;
            $itemCount  = count($indicators);

            foreach ($indicators as $indicator) {
                $indicatorId = (int) $indicator['id'];
                $response    = $responses[$indicatorId] ?? null;
                if ($response !== null && (bool) $response->checklist_value === true) {
                    $itemMet++;
                }
            }

            $totalMet   += $itemMet;
            $totalCount += $itemCount;

            $breakdown[] = [
                'item_id'   => $itemId,
                'item_name' => $item['name'] ?? null,
                'weight'    => null,
                'earned'    => $itemMet,
                'max'       => $itemCount,
                // reproducibility inputs
                'met'       => $itemMet,
                'total'     => $itemCount,
            ];
        }

        return ScoreResult::make((float) $totalMet, (float) $totalCount, $breakdown);
    }
}
