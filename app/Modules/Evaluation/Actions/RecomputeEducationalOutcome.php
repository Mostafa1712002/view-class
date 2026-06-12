<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationOutcome;
use App\Models\User;
use App\Modules\Evaluation\Enums\OutcomeApprovalStatus;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EducationalOutcomeCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase C (#205) — Re-run the calculation for an existing EvaluationOutcome.
 *
 * Guards:
 *   - Approved outcomes cannot be recomputed by a regular school-admin unless
 *     the actor is a super-admin or school-admin (per spec). Non-admins are blocked.
 *
 * The old average, old method, new average, new method, and reason are all
 * captured in the AuditTrail for full traceability.
 */
class RecomputeEducationalOutcome
{
    public function __construct(
        private readonly EducationalOutcomeCalculator $calculator,
        private readonly AuditTrail                   $audit,
    ) {
    }

    /**
     * @throws ValidationException when an approved record is edited by a non-admin.
     */
    public function execute(
        EvaluationOutcome $outcome,
        ?string           $newMethod,
        ?string           $reason,
        User              $actor,
    ): EvaluationOutcome {
        return DB::transaction(function () use ($outcome, $newMethod, $reason, $actor) {
            // Guard: approved outcomes are locked for non-admin actors.
            if (
                $outcome->approval_status === OutcomeApprovalStatus::Approved
                && !$actor->isSuperAdmin()
                && !$actor->isSchoolAdmin()
            ) {
                throw ValidationException::withMessages([
                    'outcome' => __('evaluation_outcomes.errors.approved_locked'),
                ]);
            }

            $method = $newMethod ?? $outcome->getRawOriginal('method_used') ?? 'all_registered';

            // Re-compute from the stored students snapshot.
            $stats = $this->calculator->compute($outcome->students ?? [], $method);

            $oldAverage = $outcome->final_average;
            $oldMethod  = $outcome->getRawOriginal('method_used') ?? $method;

            // Explicit assignment — these fields are not mass-assignable.
            $outcome->registered_count   = $stats['registered'];
            $outcome->present_count      = $stats['present'];
            $outcome->absent_count       = $stats['absent'];
            $outcome->scores_sum         = $stats['sum'];
            $outcome->method_used        = $method;
            $outcome->final_average      = $stats['average'];
            $outcome->last_recomputed_at = now();
            $outcome->save();

            // Audit with full before/after context.
            $this->audit->record(
                'outcome.recompute',
                "إعادة احتساب ناتج #{$outcome->id} — الطريقة: {$oldMethod} → {$method} — المتوسط: {$oldAverage} → {$stats['average']}" . ($reason ? " — السبب: {$reason}" : ''),
                $outcome,
                ['average' => $oldAverage, 'method' => $oldMethod],
                ['average' => $stats['average'], 'method' => $method, 'reason' => $reason],
            );

            return $outcome->refresh();
        });
    }
}
