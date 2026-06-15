<?php

namespace App\Modules\Support\Services;

use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\User;

/**
 * #267 — fires in-app notifications into the existing custom `notifications`
 * table (consumed by the navbar bell + /notifications). Thin + best-effort:
 * a missing recipient is simply skipped (never blocks the ticket action).
 */
class SupportNotifier
{
    private function push(?int $userId, string $title, string $body, string $color, ?SupportTicket $ticket, bool $staffUrl): void
    {
        if (! $userId) {
            return;
        }

        $url = $ticket
            ? ($staffUrl ? route('admin.support.show', $ticket->id) : route('my.support.show', $ticket->id))
            : null;

        Notification::create([
            'user_id'     => $userId,
            'type'        => 'system',
            'title'       => $title,
            'body'        => $body,
            'icon'        => 'bi-life-preserver',
            'color'       => $color,
            'action_url'  => $url,
            'action_text' => __('support.notify_action_view'),
        ]);
    }

    /**
     * New ticket → notify the assignee if set, otherwise the school's admins
     * (the support queue). School-scoped; falls back to nothing if none found.
     */
    public function ticketCreated(SupportTicket $ticket): void
    {
        if ($ticket->assigned_to) {
            $this->push($ticket->assigned_to, __('support.notify_created_title'), $ticket->subject, 'info', $ticket, true);

            return;
        }

        $adminIds = User::query()
            ->when($ticket->school_id, fn ($q) => $q->where('school_id', $ticket->school_id))
            ->whereHas('roles', fn ($q) => $q->where('slug', 'school-admin'))
            ->pluck('id');

        foreach ($adminIds as $adminId) {
            $this->push($adminId, __('support.notify_created_title'), $ticket->subject, 'info', $ticket, true);
        }
    }

    /** Staff replied → notify the ticket creator. */
    public function staffReplied(SupportTicket $ticket): void
    {
        $this->push(
            $ticket->created_by,
            __('support.notify_staff_reply_title'),
            $ticket->subject,
            'primary',
            $ticket,
            false
        );
    }

    /** User replied → notify the assignee (if any). */
    public function userReplied(SupportTicket $ticket): void
    {
        $this->push(
            $ticket->assigned_to,
            __('support.notify_user_reply_title'),
            $ticket->subject,
            'info',
            $ticket,
            true
        );
    }

    /** Status changed → notify the ticket creator. */
    public function statusChanged(SupportTicket $ticket, string $newStatus): void
    {
        $this->push(
            $ticket->created_by,
            __('support.notify_status_title', ['status' => SupportTicket::statusLabelFor($newStatus)]),
            $ticket->subject,
            $newStatus === 'closed' ? 'secondary' : 'warning',
            $ticket,
            false
        );
    }

    /** Assigned → notify the new assignee. */
    public function assigned(SupportTicket $ticket, int $assigneeId): void
    {
        $this->push(
            $assigneeId,
            __('support.notify_assigned_title'),
            $ticket->subject,
            'info',
            $ticket,
            true
        );
    }
}
