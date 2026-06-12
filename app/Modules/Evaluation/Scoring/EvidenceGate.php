<?php

namespace App\Modules\Evaluation\Scoring;

use App\Models\Evaluation;
use App\Models\EvaluationEvidence;
use App\Models\EvaluationItem;
use App\Modules\Evaluation\Enums\EvidenceStatus;

/**
 * Phase B (#204) — Evidence-approval scoring gate.
 *
 * Applied AFTER the raw scorer produces a ScoreResult. For each item in the
 * breakdown, if the corresponding EvaluationItem has `evidence_needs_approval=true`
 * AND no evidence with status='approved' exists for that item on this evaluation,
 * the item's earned score is zeroed and flagged `gated=true`.
 *
 * Non-breaking contract:
 *  - Items without `evidence_needs_approval` pass through unchanged.
 *  - Items with `evidence_needs_approval=true` that DO have approved evidence
 *    pass through unchanged.
 *  - Existing evidence (uploaded before this migration) has DEFAULT status='approved'
 *    so they all pass through — legacy evaluations are unaffected.
 *
 * ScoreResult is readonly/immutable, so we reconstruct a new one with the
 * zeroed breakdown. We never touch the scorer internals.
 */
final class EvidenceGate
{
    /**
     * Apply the gate to a ScoreResult for the given Evaluation.
     *
     * @param Evaluation  $evaluation Must have `evidences` already loaded (or loadable).
     * @param ScoreResult $result     The raw scorer output to gate.
     *
     * @return ScoreResult The gated result (may be identical to $result when nothing is gated).
     */
    public static function apply(Evaluation $evaluation, ScoreResult $result): ScoreResult
    {
        $evaluation->loadMissing('evidences');

        // Collect item_ids that appear in the breakdown so we can batch-load config.
        $breakdownItemIds = array_column($result->breakdown, 'item_id');
        if (empty($breakdownItemIds)) {
            return $result;
        }

        // Load item configs that need approval — scoped to just the breakdown items.
        $itemsNeedingApproval = EvaluationItem::query()
            ->whereIn('id', $breakdownItemIds)
            ->where('evidence_needs_approval', true)
            ->pluck('id')
            ->flip()           // id => index (for O(1) lookup)
            ->all();

        if (empty($itemsNeedingApproval)) {
            // No items in this evaluation require evidence approval — pass through.
            return $result;
        }

        // Index approved evidence by item_id for O(1) lookup.
        // Scoped through the evaluation (multi-tenant: evidences were loaded via evaluation relation).
        $approvedItemIds = [];
        foreach ($evaluation->evidences as $evidence) {
            if ($evidence->item_id !== null && $evidence->status === EvidenceStatus::Approved) {
                $approvedItemIds[(int) $evidence->item_id] = true;
            }
        }

        // Walk the breakdown and zero any gated item.
        $gatedAny    = false;
        $newBreakdown = [];
        $newTotal     = 0.0;

        foreach ($result->breakdown as $entry) {
            $itemId = (int) ($entry['item_id'] ?? 0);

            if (isset($itemsNeedingApproval[$itemId]) && empty($approvedItemIds[$itemId])) {
                // Item needs approval but has none approved yet — gate it.
                $entry['earned'] = 0.0;
                $entry['gated']  = true;
                $gatedAny        = true;
            } else {
                $entry['gated'] = false;
            }

            $newBreakdown[] = $entry;
            $newTotal      += (float) $entry['earned'];
        }

        if (!$gatedAny) {
            return $result;
        }

        // Recompute headline numbers with the same max (gated items still count toward max).
        return ScoreResult::make(round($newTotal, 2), $result->max, $newBreakdown);
    }
}
