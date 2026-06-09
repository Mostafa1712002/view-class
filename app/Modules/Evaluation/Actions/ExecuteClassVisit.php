<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\ClassVisit;
use App\Models\Evaluation;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\VisitStatus;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Validation\ValidationException;

/**
 * Task 18 — execute a class visit.
 *
 * Opens the linked form by creating (or resuming) an Evaluation bound to the
 * form's latest frozen snapshot, with class_visit_id = this visit. The supervisor
 * then fills it via the existing execution UI (admin.evaluations.execute.show).
 *
 * NOTE: this deliberately does NOT reuse StartEvaluation::start() — that action
 * scope-checks against evaluator assignments (class visits have none) and its
 * resume logic filters whereNull('class_visit_id'). Only its public
 * latestSnapshot() helper is reused.
 *
 * The Evaluation's subject is the TEACHER (a user), not the academic subject of
 * the visit (visit->subject_id is the math/science Subject, not a person).
 */
class ExecuteClassVisit
{
    public function __construct(
        private readonly StartEvaluation $starter,
        private readonly AuditTrail $audit,
    ) {
    }

    /**
     * @throws ValidationException when the visit has no form or no published snapshot.
     */
    public function execute(ClassVisit $visit, int $evaluatorId): Evaluation
    {
        // Idempotent: re-running execute on a visit that already spawned an
        // evaluation resumes it rather than creating duplicates.
        if ($visit->evaluation_id) {
            $existing = Evaluation::query()->whereKey($visit->evaluation_id)->first();
            if ($existing) {
                return $existing;
            }
        }

        $form = $visit->form;
        if (!$form) {
            throw ValidationException::withMessages([
                'form' => __('class_visits.errors.no_form'),
            ]);
        }

        if ($form->status?->value !== 'published') {
            throw ValidationException::withMessages([
                'form' => __('class_visits.errors.form_not_eligible'),
            ]);
        }

        $snapshot = $this->starter->latestSnapshot($form);
        if (!$snapshot) {
            throw ValidationException::withMessages([
                'form' => __('class_visits.errors.form_not_eligible'),
            ]);
        }

        // Resume a still-open evaluation already tied to this visit (defensive:
        // covers the case where evaluation_id was lost but the row survives).
        $existing = Evaluation::query()
            ->where('class_visit_id', $visit->id)
            ->where('evaluator_id', $evaluatorId)
            ->latest('id')
            ->first();

        if ($existing) {
            $this->bindVisit($visit, $existing->id);

            return $existing;
        }

        $evaluation = Evaluation::create([
            'form_id'        => $form->id,
            'snapshot_id'    => $snapshot->id,
            'evaluator_id'   => $evaluatorId,
            'subject_type'   => 'user',
            'subject_id'     => $visit->teacher_id,   // the teacher being visited
            'school_id'      => $form->school_id ?? $visit->school_id,
            'class_visit_id' => $visit->id,
            'status'         => EvaluationStatus::Draft,
        ]);

        $this->bindVisit($visit, $evaluation->id);

        $this->audit->record(
            'visit.execute',
            "تنفيذ زيارة صفية #{$visit->id} عبر النموذج «{$form->title}» (تقييم #{$evaluation->id})",
            $visit,
            null,
            ['evaluation_id' => $evaluation->id]
        );

        return $evaluation;
    }

    /** Link the evaluation to the visit and flip the visit into in-progress. */
    private function bindVisit(ClassVisit $visit, int $evaluationId): void
    {
        $payload = ['evaluation_id' => $evaluationId];

        // Don't downgrade a completed visit; otherwise mark it in-progress.
        if ($visit->status !== VisitStatus::Completed) {
            $payload['status'] = VisitStatus::InProgress->value;
        }

        $visit->fill($payload)->save();
    }
}
