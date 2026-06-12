<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Models\EvaluationEvidence;
use App\Models\User;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\EvidenceStatus;
use App\Modules\Evaluation\Scoring\EvidenceGate;
use App\Modules\Evaluation\Scoring\ScoringStrategyFactory;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Phase B (#204) — Review (approve / reject / request-edit) a single evidence.
 *
 * After any decision, re-scores the parent evaluation if it is in a scoreable
 * state (not draft, not already frozen-approved). This lets the score reflect
 * the approval in real time without the evaluator having to re-submit.
 */
class ReviewEvidence
{
    /**
     * Evaluation statuses where re-scoring after an evidence review makes sense.
     * Draft: nothing submitted yet — no score to recompute.
     * Approved: frozen — do not silently overwrite without a dedicated reopen.
     * Locked: same as approved.
     */
    private const RESCORE_ALLOWED = [
        EvaluationStatus::Completed,
        EvaluationStatus::PendingApproval,
        EvaluationStatus::NeedsReview,
        EvaluationStatus::Rejected,
    ];

    public function __construct(private readonly AuditTrail $audit)
    {
    }

    /**
     * Record a review decision on an evidence record.
     *
     * @param EvaluationEvidence $evidence  The evidence being reviewed.
     * @param string             $decision  One of: approved | rejected | needs_edit.
     * @param string|null        $note      Required when decision is 'rejected'.
     * @param User               $actor     The reviewer taking the action.
     *
     * @throws ValidationException on invalid decision or missing rejection note.
     */
    public function execute(
        EvaluationEvidence $evidence,
        string $decision,
        ?string $note,
        User $actor,
    ): EvaluationEvidence {
        $newStatus = match ($decision) {
            'approved'   => EvidenceStatus::Approved,
            'rejected'   => EvidenceStatus::Rejected,
            'needs_edit' => EvidenceStatus::NeedsEdit,
            default      => throw ValidationException::withMessages([
                'decision' => __('evaluation.evidence_review_invalid_decision'),
            ]),
        };

        if ($newStatus === EvidenceStatus::Rejected && trim((string) $note) === '') {
            throw ValidationException::withMessages([
                'note' => __('evaluation.evidence_reject_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($evidence, $newStatus, $note, $actor) {
            $old = $evidence->toArray();

            $evidence->fill([
                'status'      => $newStatus,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            $this->audit->record(
                'evidence.review',
                "مراجعة شاهد #{$evidence->id} — القرار: {$newStatus->value}",
                $evidence,
                $old,
                $evidence->toArray()
            );

            // Re-score parent evaluation if in a rescoreable state.
            $this->rescoreParent($evidence);

            return $evidence->refresh();
        });
    }

    /**
     * Re-score the parent evaluation after an evidence status change.
     * Only runs when the evaluation is in a state that has already been scored
     * (i.e., submitted). Approved evaluations are frozen — skip silently.
     */
    private function rescoreParent(EvaluationEvidence $evidence): void
    {
        $evaluation = $evidence->evaluation;
        if (!$evaluation) {
            return;
        }

        $status = $evaluation->status;
        if (!$status instanceof EvaluationStatus) {
            return;
        }

        if (!in_array($status, self::RESCORE_ALLOWED, true)) {
            return;
        }

        $evaluation->loadMissing(['form', 'snapshot', 'responses', 'evidences']);

        $form     = $evaluation->form;
        $snapshot = $evaluation->snapshot;
        if (!$snapshot) {
            return;
        }

        $payload = $snapshot->payload ?? [];

        // Resolve form type (mirrors SubmitEvaluation).
        $type = $form?->type
            ?? \App\Modules\Evaluation\Enums\FormType::tryFrom(
                (string) ($payload['form']['type'] ?? '')
            );

        if ($type === null) {
            return;
        }

        // Re-score via the same pipeline: scorer → evidence gate.
        $result = (new ScoringStrategyFactory())->for($type)->score($evaluation, $payload);
        $result = EvidenceGate::apply($evaluation, $result);

        $evaluation->fill([
            'total_score'     => $result->total,
            'max_score'       => $result->max,
            'percentage'      => $result->percentage,
            'grade_label'     => $result->gradeLabel,
            'score_breakdown' => $result->toArray(),
        ])->save();
    }
}
