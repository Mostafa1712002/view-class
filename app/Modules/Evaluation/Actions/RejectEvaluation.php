<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 14 — reject an evaluation (a reason is mandatory).
 *
 * Allowed from pending_approval / completed / needs_review. Flips status to
 * rejected, stores the reason, audits, and notifies the evaluator. A rejected
 * evaluation can later be reopened to draft by an authorised admin.
 */
class RejectEvaluation
{
    private const REJECTABLE = ['pending_approval', 'completed', 'needs_review'];

    public function __construct(
        private readonly AuditTrail $audit,
        private readonly EvaluationNotifier $notifier,
    ) {
    }

    /** @throws ValidationException on illegal transition or empty reason */
    public function execute(Evaluation $evaluation, int $actorId, ?string $reason): Evaluation
    {
        if (!in_array($evaluation->status?->value, self::REJECTABLE, true)) {
            throw ValidationException::withMessages([
                'approval' => __('eval_approval.errors.cannot_reject'),
            ]);
        }

        $reason = trim((string) $reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => __('eval_approval.errors.reason_required'),
            ]);
        }

        return DB::transaction(function () use ($evaluation, $actorId, $reason) {
            $old = $evaluation->toArray();

            $evaluation->fill([
                'status'           => EvaluationStatus::Rejected,
                'rejection_reason' => $reason,
                'approved_by'      => null,
                'approved_at'      => null,
            ])->save();

            $this->notifier->evaluationRejected($evaluation);

            $this->audit->record(
                'reject',
                "رفض تقييم #{$evaluation->id} — السبب: {$reason}",
                $evaluation,
                $old,
                $evaluation->toArray()
            );

            return $evaluation->refresh();
        });
    }
}
