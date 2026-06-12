<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Models\EvaluationForm;
use App\Models\EvaluationFormSnapshot;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Validation\ValidationException;

/**
 * Task 11 — start (or resume) an evaluation for a given evaluator × subject × form.
 *
 * On start the evaluation binds to the form's LATEST snapshot so scoring always
 * runs against the frozen structure (later form edits never alter the result).
 * Resuming returns the existing non-locked draft instead of creating duplicates.
 */
class StartEvaluation
{
    public function __construct(private readonly AuditTrail $audit)
    {
    }

    /**
     * @throws ValidationException when the form is not published, has no snapshot,
     *                             or the subject is outside the evaluator's scope.
     */
    public function start(EvaluationForm $form, int $evaluatorId, int $subjectId): Evaluation
    {
        if ($form->status?->value !== 'published') {
            throw ValidationException::withMessages([
                'form' => __('evaluation.execute.errors.not_published'),
            ]);
        }

        $snapshot = $this->latestSnapshot($form);
        if (!$snapshot) {
            throw ValidationException::withMessages([
                'form' => __('evaluation.execute.errors.no_snapshot'),
            ]);
        }

        // Authorize: subject must belong to a target assigned to THIS evaluator.
        if (!$this->subjectInScope($form, $evaluatorId, $subjectId)) {
            throw ValidationException::withMessages([
                'subject' => __('evaluation.execute.errors.subject_not_in_scope'),
            ]);
        }

        // --- Phase E (#202): Shared mode branch ---
        // In shared_mode the form has ONE evaluation per subject regardless of which
        // evaluator is starting. All evaluators for this subject converge on the SAME row.
        if ($form->shared_mode) {
            return $this->startShared($form, $snapshot, $subjectId);
        }

        // --- Legacy branch (shared_mode=0): ONE evaluation per (evaluator, subject) ---
        // Resume an existing in-progress evaluation rather than duplicating.
        $existing = Evaluation::query()
            ->where('form_id', $form->id)
            ->where('evaluator_id', $evaluatorId)
            ->where('subject_type', 'user')
            ->where('subject_id', $subjectId)
            ->whereNull('class_visit_id')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $evaluation = Evaluation::create([
            'form_id'      => $form->id,
            'snapshot_id'  => $snapshot->id,
            'evaluator_id' => $evaluatorId,
            'subject_type' => 'user',
            'subject_id'   => $subjectId,
            'school_id'    => $form->school_id,
            'status'       => EvaluationStatus::Draft,
        ]);

        $this->audit->record(
            'start',
            "بدء تقييم على النموذج «{$form->title}» (مقيّم #{$evaluatorId} ← مستهدف #{$subjectId})",
            $evaluation,
            null,
            $evaluation->toArray()
        );

        return $evaluation;
    }

    /**
     * Phase E (#202) — Find-or-create the SINGLE shared evaluation for a (form, subject) pair.
     *
     * evaluator_id is left NULL on the shared row; individual contributions are tracked
     * through evaluation_responses.filled_by instead.
     */
    private function startShared(EvaluationForm $form, \App\Models\EvaluationFormSnapshot $snapshot, int $subjectId): Evaluation
    {
        // The shared evaluation is keyed by (form_id, subject_id) with no evaluator constraint.
        $existing = Evaluation::query()
            ->where('form_id', $form->id)
            ->where('subject_type', 'user')
            ->where('subject_id', $subjectId)
            ->whereNull('class_visit_id')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $evaluation = Evaluation::create([
            'form_id'      => $form->id,
            'snapshot_id'  => $snapshot->id,
            'evaluator_id' => null,   // shared: no single evaluator owns this row
            'subject_type' => 'user',
            'subject_id'   => $subjectId,
            'school_id'    => $form->school_id,
            'status'       => EvaluationStatus::Draft,
        ]);

        $this->audit->record(
            'start_shared',
            "بدء تقييم مشترك على النموذج «{$form->title}» (مستهدف #{$subjectId})",
            $evaluation,
            null,
            $evaluation->toArray()
        );

        return $evaluation;
    }

    /** The form's most recent frozen snapshot (highest version). */
    public function latestSnapshot(EvaluationForm $form): ?EvaluationFormSnapshot
    {
        return $form->snapshots()->orderByDesc('version')->first();
    }

    /** Is this subject assigned to this evaluator on this form? */
    private function subjectInScope(EvaluationForm $form, int $evaluatorId, int $subjectId): bool
    {
        $assignment = $form->assignments()
            ->where('evaluator_id', $evaluatorId)
            ->with('targets:id,target_id,target_type')
            ->first();

        if (!$assignment) {
            return false;
        }

        return $assignment->targets->contains(
            fn ($t) => $t->target_type === 'user' && (int) $t->target_id === $subjectId
        );
    }
}
