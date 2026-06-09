<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationAssignment;
use App\Models\EvaluationForm;
use App\Models\User;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 7 — assign an evaluator to a form and link them to the subset of the form's
 * targets they will evaluate.
 *
 * Rules:
 *  - An evaluator may not evaluate themselves unless the form enables
 *    `allow_self_eval` (default false → such a target is rejected).
 *  - Each assignment carries only targets that actually belong to the form.
 *  - Every assignment create/update is written to the audit trail.
 */
class AssignEvaluators
{
    public function __construct(
        private readonly AuditTrail $audit,
        private readonly EvaluationNotifier $notifier,
    ) {
    }

    /**
     * Create or update an evaluator assignment with its target scope.
     *
     * @param  int[]  $targetIds  evaluation_targets.id values to link
     */
    public function assign(EvaluationForm $form, int $evaluatorId, array $targetIds, int $actorId): EvaluationAssignment
    {
        $evaluator = User::query()->find($evaluatorId);
        if (!$evaluator) {
            throw ValidationException::withMessages([
                'evaluator_id' => __('evaluation.evaluators.errors.user_not_found'),
            ]);
        }

        // Keep only target ids that belong to this form.
        $validTargets = $form->targets()
            ->whereIn('id', array_map('intval', $targetIds))
            ->get(['id', 'target_type', 'target_id']);

        // Self-evaluation guard.
        if (!$form->setting('allow_self_eval', false)) {
            $self = $validTargets->first(
                fn ($t) => $t->target_type === 'user' && (int) $t->target_id === $evaluatorId
            );
            if ($self) {
                throw ValidationException::withMessages([
                    'evaluator_id' => __('evaluation.evaluators.errors.self_eval_blocked'),
                ]);
            }
        }

        return DB::transaction(function () use ($form, $evaluatorId, $evaluator, $validTargets, $actorId) {
            $assignment = $form->assignments()->firstOrNew(['evaluator_id' => $evaluatorId]);
            $isNew      = !$assignment->exists;
            $old        = $isNew ? null : $assignment->toArray();

            if ($isNew) {
                $assignment->status      = 'assigned';
                $assignment->assigned_at = now();
            }
            $assignment->save();

            $assignment->targets()->sync($validTargets->pluck('id')->all());

            if ($isNew) {
                $this->audit->record(
                    'evaluator.assign',
                    "تكليف مقيّم «{$evaluator->name}» على النموذج «{$form->title}» ({$validTargets->count()} مستهدف)",
                    $assignment,
                    null,
                    $assignment->toArray()
                );

                // Notify the newly-assigned evaluator (trigger: evaluator-assigned).
                // Only on first assignment — scope updates below do not re-notify.
                $this->notifier->evaluatorAssigned($form, [$evaluatorId]);
            } else {
                $this->audit->record(
                    'evaluator.update',
                    "تعديل نطاق المقيّم «{$evaluator->name}» على النموذج «{$form->title}» ({$validTargets->count()} مستهدف)",
                    $assignment,
                    $old,
                    $assignment->toArray()
                );
            }

            return $assignment->refresh();
        });
    }

    /** Remove an evaluator assignment and its target links. */
    public function remove(EvaluationForm $form, int $assignmentId, int $actorId): bool
    {
        $assignment = $form->assignments()->whereKey($assignmentId)->with('evaluator:id,name')->first();
        if (!$assignment) {
            return false;
        }

        return (bool) DB::transaction(function () use ($form, $assignment) {
            $name = $assignment->evaluator?->name ?? ('#'.$assignment->evaluator_id);
            $this->audit->record(
                'evaluator.remove',
                "إلغاء تكليف المقيّم «{$name}» من النموذج «{$form->title}»",
                $assignment,
                $assignment->toArray(),
                null
            );
            $assignment->targets()->detach();

            return $assignment->delete();
        });
    }
}
