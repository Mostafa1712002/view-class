<?php

namespace App\Modules\Promotion\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to school admins when a promotion run cannot place a group of students
 * because the destination class number is full or missing (US-008).
 */
class ClassCapacityExceeded extends Notification
{
    use Queueable;

    public function __construct(
        public string $gradeName,
        public int $classNumber,
        public int $count,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'promotion_capacity_exceeded',
            'title' => __('schools.promotion_notify_title'),
            'message' => __('schools.promotion_notify_body', [
                'count' => $this->count,
                'grade' => $this->gradeName,
                'number' => $this->classNumber,
            ]),
            'grade' => $this->gradeName,
            'number' => $this->classNumber,
            'count' => $this->count,
        ];
    }
}
