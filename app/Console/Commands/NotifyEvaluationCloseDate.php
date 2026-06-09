<?php

namespace App\Console\Commands;

use App\Models\Evaluation;
use App\Models\EvaluationAssignment;
use App\Models\EvaluationForm;
use App\Models\Notification;
use App\Modules\Evaluation\Enums\FormStatus;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Console\Command;

/**
 * Sprint 8 P7 — close-date-approaching reminder (notification trigger #13).
 *
 * Finds PUBLISHED forms whose close_date falls within the next 48h (and has not
 * yet passed), then notifies each assigned evaluator who STILL has incomplete
 * subjects on that form. Idempotent per (evaluator, form, day): a matching
 * unread reminder created today suppresses a second one.
 *
 * Scheduled daily at 07:00 (see routes/console.php).
 */
class NotifyEvaluationCloseDate extends Command
{
    protected $signature = 'evaluation:notify-close-date';

    protected $description = 'Notify evaluators with incomplete subjects when a published form close-date is within 48h';

    public function handle(EvaluationNotifier $notifier): int
    {
        $now      = now();
        $deadline = $now->copy()->addHours(48);

        $forms = EvaluationForm::query()
            ->where('status', FormStatus::Published->value)
            ->whereNotNull('close_date')
            ->whereBetween('close_date', [$now, $deadline])
            ->get();

        if ($forms->isEmpty()) {
            $this->info('No published forms approaching their close date.');

            return self::SUCCESS;
        }

        $notified = 0;

        foreach ($forms as $form) {
            $evaluatorIds = $this->incompleteEvaluatorIds($form);

            foreach ($evaluatorIds as $evaluatorId) {
                if ($this->alreadyNotifiedToday($evaluatorId, $form->id)) {
                    continue;
                }

                $notifier->closeDateApproaching($form, [$evaluatorId]);
                $notified++;
            }
        }

        $this->info("Close-date reminders sent: {$notified}.");

        return self::SUCCESS;
    }

    /**
     * Evaluators assigned to the form who still have at least one un-submitted
     * subject. Mirrors MyEvaluationsController::requiredOfMe completion logic
     * (a non-draft evaluation by the evaluator for the subject = done).
     *
     * @return int[]
     */
    private function incompleteEvaluatorIds(EvaluationForm $form): array
    {
        $assignments = EvaluationAssignment::query()
            ->where('form_id', $form->id)
            ->with('targets:id,target_id')
            ->get();

        $incomplete = [];

        foreach ($assignments as $assignment) {
            $subjectIds = $assignment->targets
                ->pluck('target_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($subjectIds === []) {
                continue; // nothing assigned → nothing to chase
            }

            $done = Evaluation::query()
                ->where('form_id', $form->id)
                ->where('evaluator_id', $assignment->evaluator_id)
                ->whereIn('subject_id', $subjectIds)
                ->where('subject_type', 'user')
                ->where('status', '!=', 'draft')
                ->distinct('subject_id')
                ->count('subject_id');

            if ($done < count($subjectIds)) {
                $incomplete[] = (int) $assignment->evaluator_id;
            }
        }

        return array_values(array_unique($incomplete));
    }

    /** True when this evaluator already got a close-date reminder for this form today. */
    private function alreadyNotifiedToday(int $evaluatorId, int $formId): bool
    {
        return Notification::query()
            ->where('user_id', $evaluatorId)
            ->where('type', 'evaluation_close_date')
            ->where('data->form_id', $formId)
            ->whereDate('created_at', today())
            ->exists();
    }
}
