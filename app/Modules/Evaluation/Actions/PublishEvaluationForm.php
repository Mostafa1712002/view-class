<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationForm;
use App\Models\EvaluationFormSnapshot;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use App\Modules\Evaluation\Services\FormCompletenessChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 8 — publish / close / archive an evaluation form.
 *
 * Publishing freezes the entire authored structure into an immutable
 * EvaluationFormSnapshot (so later edits to the form never alter past results),
 * flips the form to `published`, locks structural edits (via FormStatus::isEditable),
 * and notifies every assigned evaluator.
 */
class PublishEvaluationForm
{
    public function __construct(
        private readonly FormCompletenessChecker $checker,
        private readonly EvaluationNotifier $notifier,
        private readonly AuditTrail $audit,
    ) {
    }

    /**
     * Publish a draft/ready form. Returns the created snapshot.
     *
     * @throws ValidationException when the form is not publishable or in a bad state
     */
    public function publish(EvaluationForm $form, int $actorId): EvaluationFormSnapshot
    {
        if (!in_array($form->status?->value, ['draft', 'ready'], true)) {
            throw ValidationException::withMessages([
                'status' => __('evaluation.publish.errors.not_draft'),
            ]);
        }

        $problems = $this->checker->problems($form);
        if ($problems !== []) {
            throw ValidationException::withMessages(['publish' => $problems]);
        }

        return DB::transaction(function () use ($form, $actorId) {
            $version  = (int) ($form->snapshots()->max('version') ?? 0) + 1;
            $snapshot = EvaluationFormSnapshot::create([
                'form_id'      => $form->id,
                'version'      => $version,
                'payload'      => $this->buildPayload($form),
                'published_by' => $actorId,
                'published_at' => now(),
            ]);

            $old = $form->toArray();
            $form->status       = \App\Modules\Evaluation\Enums\FormStatus::Published;
            $form->published_at = now();
            $form->save();

            $evaluatorIds = $form->assignments()->pluck('evaluator_id')->map(fn ($id) => (int) $id)->all();
            if ($form->setting('notify_on_publish', true)) {
                $this->notifier->formPublished($form, $evaluatorIds);
            }

            $this->audit->record(
                'form.publish',
                "نشر نموذج التقييم «{$form->title}» (إصدار {$version})",
                $form,
                $old,
                $form->toArray()
            );

            return $snapshot;
        });
    }

    /** Close a published form (no further evaluations can be started). */
    public function close(EvaluationForm $form, int $actorId): void
    {
        if ($form->status?->value !== 'published') {
            throw ValidationException::withMessages([
                'status' => __('evaluation.publish.errors.not_published'),
            ]);
        }

        $old = $form->toArray();
        $form->status    = \App\Modules\Evaluation\Enums\FormStatus::Closed;
        $form->closed_at = now();
        $form->save();

        $this->audit->record('form.close', "إغلاق نموذج التقييم «{$form->title}»", $form, $old, $form->toArray());
    }

    /** Archive a form (allowed when it has real evaluations instead of deleting). */
    public function archive(EvaluationForm $form, int $actorId): void
    {
        if (!in_array($form->status?->value, ['published', 'closed', 'ready', 'draft'], true)) {
            throw ValidationException::withMessages([
                'status' => __('evaluation.publish.errors.cannot_archive'),
            ]);
        }

        $old = $form->toArray();
        $form->status      = \App\Modules\Evaluation\Enums\FormStatus::Archived;
        $form->archived_at = now();
        $form->save();

        $this->audit->record('form.archive', "أرشفة نموذج التقييم «{$form->title}»", $form, $old, $form->toArray());
    }

    /**
     * Freeze the full authored tree into a plain, stable array. Built by explicit
     * mapping (not toArray) so the execution layer always reads a known shape and
     * no soft-delete/timestamp noise leaks into the snapshot.
     */
    private function buildPayload(EvaluationForm $form): array
    {
        $form->loadMissing(['levels', 'items.indicators']);

        return [
            'form' => [
                'id'                       => $form->id,
                'title'                    => $form->title,
                'description'              => $form->description,
                'type'                     => $form->type?->value,
                'usage_domain'             => $form->usage_domain?->value,
                'levels_count'             => $form->levels_count,
                'start_date'               => $form->start_date?->toDateTimeString(),
                'close_date'               => $form->close_date?->toDateTimeString(),
                'is_class_visit_only'      => (bool) $form->is_class_visit_only,
                'links_to_job_performance' => (bool) $form->links_to_job_performance,
                'settings'                 => $form->settings ?? [],
                'job_perf_settings'        => $form->job_perf_settings ?? [],
            ],
            'levels' => $form->levels->map(fn ($l) => [
                'id'         => $l->id,
                'label'      => $l->label,
                'value'      => (float) $l->value,
                'percentage' => $l->percentage !== null ? (float) $l->percentage : null,
                'sort_order' => (int) $l->sort_order,
            ])->values()->all(),
            'items' => $form->items->map(fn ($item) => [
                'id'                              => $item->id,
                'name'                            => $item->name,
                'description'                     => $item->description,
                'sort_order'                      => (int) $item->sort_order,
                'weight'                          => (float) $item->weight,
                'max_score'                       => (float) $item->max_score,
                'is_required'                     => (bool) $item->is_required,
                'needs_evidence'                  => (bool) $item->needs_evidence,
                'evidence_required'               => (bool) $item->evidence_required,
                'allow_note'                      => (bool) $item->allow_note,
                'visible_to_evaluator_only'       => (bool) $item->visible_to_evaluator_only,
                'visible_to_subject_after_result' => (bool) $item->visible_to_subject_after_result,
                'status'                          => $item->status,
                'indicators'                      => $item->indicators->map(fn ($ind) => [
                    'id'                => $ind->id,
                    'level_id'          => $ind->level_id,
                    'text'              => $ind->text,
                    'description'       => $ind->description,
                    'sort_order'        => (int) $ind->sort_order,
                    'is_required'       => (bool) $ind->is_required,
                    'needs_note'        => (bool) $ind->needs_note,
                    'needs_evidence'    => (bool) $ind->needs_evidence,
                    'evidence_required' => (bool) $ind->evidence_required,
                    'status'            => $ind->status,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
