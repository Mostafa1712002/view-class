<?php

namespace App\Modules\Evaluation\Scoring;

use App\Models\Evaluation;

/**
 * A type-specific scoring algorithm.
 *
 * Implementations read the frozen weights/levels/indicators from the decoded
 * snapshot payload (NOT from the live form — past results must never change)
 * and the evaluator's choices from $evaluation->responses. They return a pure
 * {@see ScoreResult}; they persist nothing.
 *
 * Partial data is handled gracefully: a missing response for a node contributes
 * 0 earned (rubric/rating) or counts as not-met (checklist). Submit-completeness
 * is enforced by the caller, not here.
 */
interface ScoringStrategy
{
    /**
     * @param Evaluation                $evaluation      Carries ->responses (item_id, indicator_id, level_id, checklist_value).
     * @param array<string,mixed>       $snapshotPayload Decoded EvaluationFormSnapshot->payload (form/levels/items tree).
     */
    public function score(Evaluation $evaluation, array $snapshotPayload): ScoreResult;
}
