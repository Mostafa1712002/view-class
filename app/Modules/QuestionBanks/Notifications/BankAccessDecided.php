<?php

namespace App\Modules\QuestionBanks\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** To the requesting school's admins: the owner approved or rejected the request. */
class BankAccessDecided extends Notification
{
    use Queueable;

    public function __construct(
        public int $bankId,
        public string $bankName,
        public bool $approved,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'qb_access_decided',
            'title' => __('question_banks.notify_decided_title'),
            'message' => __($this->approved ? 'question_banks.notify_approved_body' : 'question_banks.notify_rejected_body', ['bank' => $this->bankName]),
            'bank_id' => $this->bankId,
            'approved' => $this->approved,
        ];
    }
}
