<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 14 — reopen a finalised evaluation (reopen-by-permission).
 *
 * Allowed from approved / rejected / needs_review / locked. Returns the
 * evaluation to draft (per the design state machine: "reopen (perm) → draft")
 * so the evaluator can edit and re-submit. Clears stale approval/rejection
 * metadata, audits, and notifies the evaluator. Permission is enforced by the
 * controller (super-admin or school-admin); this action assumes it is allowed.
 */
class ReopenEvaluation
{
    private const REOPENABLE = ['approved', 'rejected', 'needs_review', 'locked'];

    public function __construct(
        private readonly AuditTrail $audit,
        private readonly EvaluationNotifier $notifier,
    ) {
    }

    /** @throws ValidationException on illegal transition */
    public function execute(Evaluation $evaluation, int $actorId): Evaluation
    {
        if (!in_array($evaluation->status?->value, self::REOPENABLE, true)) {
            throw ValidationException::withMessages([
                'approval' => __('eval_approval.errors.cannot_reopen'),
            ]);
        }

        return DB::transaction(function () use ($evaluation, $actorId) {
            $old = $evaluation->toArray();

            $evaluation->fill([
                'status'           => EvaluationStatus::Draft,
                'approved_by'      => null,
                'approved_at'      => null,
                'rejection_reason' => null,
                'submitted_at'     => null,
            ])->save();

            $this->notifier->notify(
                [$evaluation->evaluator_id],
                'evaluation_reopened',
                __('eval_approval.notify.reopen_title'),
                __('eval_approval.notify.reopen_body'),
                ['evaluation_id' => $evaluation->id],
                'info',
                'bi-unlock'
            );

            $this->audit->record(
                'reopen',
                "إعادة فتح تقييم #{$evaluation->id}",
                $evaluation,
                $old,
                $evaluation->toArray()
            );

            return $evaluation->refresh();
        });
    }
}
