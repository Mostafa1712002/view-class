<?php

namespace App\Modules\Evaluation\Scoring\Concerns;

use App\Models\Evaluation;

/**
 * Shared helpers for the scoring strategies: pulling active items out of the
 * frozen snapshot payload and keying the evaluator's responses for O(1) lookup.
 */
trait ReadsSnapshot
{
    /**
     * Active items from the snapshot. Items frozen with status === 'disabled'
     * are excluded from BOTH earned and max so they neither contribute nor
     * inflate the denominator.
     *
     * @param  array<string,mixed> $payload
     * @return array<int,array<string,mixed>>
     */
    protected function activeItems(array $payload): array
    {
        $items = $payload['items'] ?? [];

        return array_values(array_filter(
            is_array($items) ? $items : [],
            fn ($item) => is_array($item) && ($item['status'] ?? 'active') !== 'disabled'
        ));
    }

    /**
     * Active indicators of an item (status !== 'disabled').
     *
     * @param  array<string,mixed> $item
     * @return array<int,array<string,mixed>>
     */
    protected function activeIndicators(array $item): array
    {
        $indicators = $item['indicators'] ?? [];

        return array_values(array_filter(
            is_array($indicators) ? $indicators : [],
            fn ($ind) => is_array($ind) && ($ind['status'] ?? 'active') !== 'disabled'
        ));
    }

    /**
     * The evaluator's responses keyed by item_id, for nodes WITHOUT an indicator
     * (rubric: one response per item).
     *
     * @return array<int,\App\Models\EvaluationResponse>
     */
    protected function responsesByItem(Evaluation $evaluation): array
    {
        $out = [];
        foreach ($evaluation->responses as $response) {
            if ($response->indicator_id === null && $response->item_id !== null) {
                $out[(int) $response->item_id] = $response;
            }
        }

        return $out;
    }

    /**
     * The evaluator's responses keyed by indicator_id (rating scale / checklist:
     * one response per indicator).
     *
     * @return array<int,\App\Models\EvaluationResponse>
     */
    protected function responsesByIndicator(Evaluation $evaluation): array
    {
        $out = [];
        foreach ($evaluation->responses as $response) {
            if ($response->indicator_id !== null) {
                $out[(int) $response->indicator_id] = $response;
            }
        }

        return $out;
    }
}
