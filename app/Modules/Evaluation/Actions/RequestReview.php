<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 14 — request a review of an evaluation (notes mandatory; item ids optional).
 *
 * Allowed from pending_approval / completed / approved. Flips status to
 * needs_review, audits, and notifies the evaluator. There is no dedicated
 * review-notes column, so the notes + flagged item ids are carried on the
 * notification payload and the audit record (the show screen reads them back
 * from the latest review notification).
 */
class RequestReview
{
    private const REVIEWABLE = ['pending_approval', 'completed', 'approved'];

    public function __construct(
        private readonly AuditTrail $audit,
        private readonly EvaluationNotifier $notifier,
    ) {
    }

    /**
     * @param int[] $itemIds items the approver flagged for review
     *
     * @throws ValidationException on illegal transition or empty notes
     */
    public function execute(Evaluation $evaluation, int $actorId, ?string $notes, array $itemIds = []): Evaluation
    {
        if (!in_array($evaluation->status?->value, self::REVIEWABLE, true)) {
            throw ValidationException::withMessages([
                'approval' => __('eval_approval.errors.cannot_review'),
            ]);
        }

        $notes = trim((string) $notes);
        if ($notes === '') {
            throw ValidationException::withMessages([
                'review_notes' => __('eval_approval.errors.notes_required'),
            ]);
        }

        $itemIds = array_values(array_unique(array_map('intval', array_filter($itemIds))));

        return DB::transaction(function () use ($evaluation, $actorId, $notes, $itemIds) {
            $old = $evaluation->toArray();

            $evaluation->fill([
                'status'      => EvaluationStatus::NeedsReview,
                'approved_by' => null,
                'approved_at' => null,
            ])->save();

            $this->notifier->notify(
                [$evaluation->evaluator_id],
                'evaluation_review',
                __('eval_approval.notify.review_title'),
                __('eval_approval.notify.review_body'),
                [
                    'evaluation_id' => $evaluation->id,
                    'review_notes'  => $notes,
                    'review_items'  => $itemIds,
                ],
                'warning',
                'bi-exclamation-triangle'
            );

            $this->audit->record(
                'review',
                "طلب مراجعة تقييم #{$evaluation->id} — ملاحظات: {$notes}",
                $evaluation,
                $old,
                $evaluation->toArray() + ['review_notes' => $notes, 'review_items' => $itemIds]
            );

            return $evaluation->refresh();
        });
    }
}
