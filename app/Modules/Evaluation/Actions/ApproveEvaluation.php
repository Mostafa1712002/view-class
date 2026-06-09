<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 14 — approve an evaluation.
 *
 * Allowed from pending_approval / completed / needs_review. Flips status to
 * approved (which locks the evaluation), stamps approver + timestamp, clears any
 * stale rejection reason, audits, notifies the evaluator, and — when the form
 * lets the subject view results — fires the result-available notification.
 * Guards on missing mandatory evidence (re-checking the snapshot, mirroring the
 * evidence subset of SubmitEvaluation's gate).
 */
class ApproveEvaluation
{
    private const APPROVABLE = ['pending_approval', 'completed', 'needs_review'];

    public function __construct(
        private readonly AuditTrail $audit,
        private readonly EvaluationNotifier $notifier,
    ) {
    }

    /** @throws ValidationException on illegal transition or missing required evidence */
    public function execute(Evaluation $evaluation, int $actorId): Evaluation
    {
        if (!in_array($evaluation->status?->value, self::APPROVABLE, true)) {
            throw ValidationException::withMessages([
                'approval' => __('eval_approval.errors.cannot_approve'),
            ]);
        }

        $missing = $this->missingEvidence($evaluation);
        if ($missing !== null) {
            throw ValidationException::withMessages([
                'approval' => __('eval_approval.errors.evidence_missing', ['node' => $missing]),
            ]);
        }

        return DB::transaction(function () use ($evaluation, $actorId) {
            $old = $evaluation->toArray();

            $evaluation->fill([
                'status'           => EvaluationStatus::Approved,
                'approved_by'      => $actorId,
                'approved_at'      => now(),
                'rejection_reason' => null,
            ])->save();

            $this->notifier->evaluationApproved($evaluation);

            // Surface the result to the subject if the form allows it.
            $form = $evaluation->form;
            if ($form && $form->setting('allow_subject_view_results', false)
                && $form->setting('notify_on_result_available', true)) {
                $this->notifier->resultAvailable($evaluation);
            }

            $this->audit->record(
                'approve',
                "اعتماد تقييم #{$evaluation->id}",
                $evaluation,
                $old,
                $evaluation->toArray()
            );

            return $evaluation->refresh();
        });
    }

    /**
     * First node whose mandatory evidence is missing, or null when all good.
     * Mirrors the evidence half of SubmitEvaluation::gateProblems() against the
     * frozen snapshot payload.
     */
    private function missingEvidence(Evaluation $evaluation): ?string
    {
        $evaluation->loadMissing(['snapshot', 'evidences', 'form']);
        $payload = $evaluation->snapshot?->payload ?? [];
        $type    = $evaluation->form?->type
            ?? FormType::tryFrom((string) ($payload['form']['type'] ?? ''));

        $evItems = [];
        $evInds  = [];
        foreach ($evaluation->evidences as $e) {
            if ($e->indicator_id !== null) {
                $evInds[(int) $e->indicator_id] = true;
            } elseif ($e->item_id !== null) {
                $evItems[(int) $e->item_id] = true;
            }
        }

        foreach (($payload['items'] ?? []) as $item) {
            if (($item['status'] ?? 'active') === 'disabled') {
                continue;
            }
            $itemId = (int) ($item['id'] ?? 0);

            if ($type === FormType::Rubric) {
                if (!empty($item['evidence_required']) && empty($evItems[$itemId])) {
                    return $item['name'] ?? ('#'.$itemId);
                }
                continue;
            }

            foreach (($item['indicators'] ?? []) as $ind) {
                if (($ind['status'] ?? 'active') === 'disabled') {
                    continue;
                }
                $indId = (int) ($ind['id'] ?? 0);
                if (!empty($ind['evidence_required']) && empty($evInds[$indId])) {
                    return $ind['text'] ?? ('#'.$indId);
                }
            }
        }

        return null;
    }
}
