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
    /** Low-level fan-out. */
    public function notify(array $userIds, string $type, string $title, string $body, array $data = [], string $color = 'info', string $icon = 'bi-clipboard-check'): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'body'    => $body,
                'icon'    => $icon,
                'color'   => $color,
                'data'    => $data,
            ]);
        }
    }

    public function formPublished(EvaluationForm $form, array $evaluatorIds): void
    {
        $this->notify(
            $evaluatorIds,
            'evaluation_published',
            __('evaluation.notify.published_title'),
            __('evaluation.notify.published_body', ['form' => $form->title]),
            ['form_id' => $form->id],
            'success'
        );
    }

    public function evaluatorAssigned(EvaluationForm $form, array $evaluatorIds): void
    {
        $this->notify(
            $evaluatorIds,
            'evaluation_assigned',
            __('evaluation.notify.assigned_title'),
            __('evaluation.notify.assigned_body', ['form' => $form->title]),
            ['form_id' => $form->id]
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
            ['evaluation_id' => $evaluation->id]
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
            'success'
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
            'danger'
        );
    }

    public function resultAvailable(Evaluation $evaluation): void
    {
        $this->notify(
            [$evaluation->subject_id],
            'evaluation_result',
            __('evaluation.notify.result_title'),
            __('evaluation.notify.result_body'),
            ['evaluation_id' => $evaluation->id]
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
            'bi-clock-history'
        );
    }
}
