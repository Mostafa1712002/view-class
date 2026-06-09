<?php

namespace App\Modules\Evaluation\Services;

use App\Models\ClassVisit;
use App\Models\Evaluation;
use App\Models\EvaluationForm;
use App\Models\Notification;

/**
 * Emits the Sprint 8 notifications (الإشعارات المطلوبة) via the shared Notification model.
 * Covers the 13 defined triggers; each helper fans a row out to the relevant users.
 */
class EvaluationNotifier
{
    /** Low-level fan-out. $actionUrl makes the notification deep-link to the relevant page. */
    public function notify(array $userIds, string $type, string $title, string $body, array $data = [], string $color = 'info', string $icon = 'bi-clipboard-check', ?string $actionUrl = null, ?string $actionText = null): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            Notification::create([
                'user_id'     => $userId,
                'type'        => $type,
                'title'       => $title,
                'body'        => $body,
                'icon'        => $icon,
                'color'       => $color,
                'action_url'  => $actionUrl,
                'action_text' => $actionText,
                'data'        => $data,
            ]);
        }
    }

    /** Build a route URL only if the route exists (keeps the notifier safe across route changes). */
    private function url(string $name, mixed $param = null): ?string
    {
        return \Illuminate\Support\Facades\Route::has($name)
            ? ($param !== null ? route($name, $param) : route($name))
            : null;
    }

    public function formPublished(EvaluationForm $form, array $evaluatorIds): void
    {
        $this->notify(
            $evaluatorIds,
            'evaluation_published',
            __('evaluation.notify.published_title'),
            __('evaluation.notify.published_body', ['form' => $form->title]),
            ['form_id' => $form->id],
            'success',
            'bi-clipboard-check',
            $this->url('admin.my-evaluations.index')
        );
    }

    public function evaluatorAssigned(EvaluationForm $form, array $evaluatorIds): void
    {
        $this->notify(
            $evaluatorIds,
            'evaluation_assigned',
            __('evaluation.notify.assigned_title'),
            __('evaluation.notify.assigned_body', ['form' => $form->title]),
            ['form_id' => $form->id],
            'info',
            'bi-clipboard-check',
            $this->url('admin.my-evaluations.index')
        );
    }

    public function visitScheduled(ClassVisit $visit): void
    {
        // Only notify the teacher when the visit is not secret.
        if ($visit->notify_teacher && $visit->status?->value !== 'secret') {
            $this->notify(
                [$visit->teacher_id],
                'class_visit_scheduled',
                __('evaluation.notify.visit_title'),
                __('evaluation.notify.visit_body', ['date' => (string) $visit->visit_date?->format('Y-m-d')]),
                ['class_visit_id' => $visit->id]
            );
        }
    }

    public function evaluationSubmitted(Evaluation $evaluation, array $approverIds): void
    {
        $this->notify(
            $approverIds,
            'evaluation_submitted',
            __('evaluation.notify.submitted_title'),
            __('evaluation.notify.submitted_body'),
            ['evaluation_id' => $evaluation->id],
            'info',
            'bi-clipboard-check',
            $this->url('admin.evaluations.approvals.show', $evaluation->id)
        );
    }

    public function evaluationApproved(Evaluation $evaluation): void
    {
        $this->notify(
            [$evaluation->evaluator_id],
            'evaluation_approved',
            __('evaluation.notify.approved_title'),
            __('evaluation.notify.approved_body'),
            ['evaluation_id' => $evaluation->id],
            'success',
            'bi-clipboard-check',
            $this->url('admin.evaluations.execute.show', $evaluation->id)
        );
    }

    public function evaluationRejected(Evaluation $evaluation): void
    {
        $this->notify(
            [$evaluation->evaluator_id],
            'evaluation_rejected',
            __('evaluation.notify.rejected_title'),
            __('evaluation.notify.rejected_body'),
            ['evaluation_id' => $evaluation->id],
            'danger',
            'bi-clipboard-check',
            $this->url('admin.evaluations.execute.show', $evaluation->id)
        );
    }

    public function resultAvailable(Evaluation $evaluation): void
    {
        $this->notify(
            [$evaluation->subject_id],
            'evaluation_result',
            __('evaluation.notify.result_title'),
            __('evaluation.notify.result_body'),
            ['evaluation_id' => $evaluation->id],
            'info',
            'bi-clipboard-check',
            $this->url('admin.evaluations.execute.show', $evaluation->id)
        );
    }

    /**
     * Close-date-approaching reminder for evaluators who still have incomplete
     * subjects on a published form within its closing window. The form_id is on
     * the payload so the scheduled command can guard against same-day duplicates.
     */
    public function closeDateApproaching(EvaluationForm $form, array $evaluatorIds): void
    {
        $this->notify(
            $evaluatorIds,
            'evaluation_close_date',
            __('evaluation.notify.close_date_title'),
            __('evaluation.notify.close_date_body', [
                'form' => $form->title,
                'date' => (string) $form->close_date?->format('Y-m-d H:i'),
            ]),
            ['form_id' => $form->id],
            'warning',
            'bi-clock-history',
            $this->url('admin.my-evaluations.index')
        );
    }

    /**
     * Notify a teacher that their class visit is scheduled for tomorrow.
     * The class_visit_id is on the payload so the scheduled command can guard
     * against same-day duplicates (idempotent per visit × day).
     */
    public function visitReminder(ClassVisit $visit): void
    {
        $this->notify(
            [$visit->teacher_id],
            'class_visit_reminder',
            __('evaluation.notify.visit_reminder_title'),
            __('evaluation.notify.visit_reminder_body', [
                'date' => (string) $visit->visit_date?->format('Y-m-d'),
            ]),
            ['class_visit_id' => $visit->id],
            'warning',
            'bi-calendar-check'
        );
    }

    /**
     * Notify evaluator (and approver if present) that the evaluation's subject
     * has posted a comment on their result. The evaluation_id is on the payload.
     */
    public function subjectCommented(Evaluation $evaluation): void
    {
        $recipients = array_filter(array_unique([
            (int) $evaluation->evaluator_id,
            $evaluation->approved_by ? (int) $evaluation->approved_by : null,
        ]));

        $this->notify(
            array_values($recipients),
            'evaluation_commented',
            __('evaluation.notify.commented_title'),
            __('evaluation.notify.commented_body'),
            ['evaluation_id' => $evaluation->id],
            'info',
            'bi-chat-left-text',
            $this->url('admin.evaluations.execute.show', $evaluation->id)
        );
    }
}
