<?php

namespace App\Modules\QuestionBanks\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** To super-admins: a school asked to copy from an owner bank. */
class BankAccessRequested extends Notification
{
    use Queueable;

    public function __construct(
        public int $bankId,
        public string $bankName,
        public string $schoolName,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'qb_access_requested',
            'title' => __('question_banks.notify_request_title'),
            'message' => __('question_banks.notify_request_body', ['school' => $this->schoolName, 'bank' => $this->bankName]),
            'bank_id' => $this->bankId,
        ];
    }
}
