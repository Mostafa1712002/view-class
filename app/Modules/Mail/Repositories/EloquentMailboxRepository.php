<?php

namespace App\Modules\Mail\Repositories;

use App\Models\InternalMail;
use App\Models\InternalMailRecipient;
use App\Modules\Mail\Repositories\Contracts\MailboxRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentMailboxRepository implements MailboxRepository
{
    public function getFolder(int $userId, string $folder, array $filters = []): LengthAwarePaginator
    {
        $perPage = 20;

        return match ($folder) {
            'inbox'     => $this->inbox($userId, $filters, $perPage),
            'sent'      => $this->sent($userId, $filters, $perPage),
            'drafts'    => $this->drafts($userId, $filters, $perPage),
            'starred'   => $this->starred($userId, $filters, $perPage),
            'important' => $this->important($userId, $filters, $perPage),
            'task'      => $this->task($userId, $filters, $perPage),
            'archive'   => $this->archive($userId, $filters, $perPage),
            'trash'     => $this->trash($userId, $filters, $perPage),
            default     => $this->inbox($userId, $filters, $perPage),
        };
    }

    public function getFolderCounts(int $userId): array
    {
        $unreadInbox = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('trashed', false)
            ->where('archived', false)
            ->where('is_read', false)
            ->whereHas('mail', fn ($q) => $q->where('is_draft', false))
            ->count();

        $sent = InternalMail::query()
            ->where('sender_id', $userId)
            ->where('is_draft', false)
            ->count();

        $drafts = InternalMail::query()
            ->where('sender_id', $userId)
            ->where('is_draft', true)
            ->count();

        $starred = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('starred', true)
            ->where('trashed', false)
            ->count();

        $important = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('trashed', false)
            ->where('archived', false)
            ->whereHas('mail', fn ($q) => $q->where('is_draft', false)->where('importance', '!=', 'normal'))
            ->count();

        $task = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('is_task', true)
            ->where('trashed', false)
            ->count();

        $archive = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('archived', true)
            ->where('trashed', false)
            ->count();

        $trash = InternalMailRecipient::query()
            ->where('recipient_id', $userId)
            ->where('trashed', true)
            ->count();

        return compact('unreadInbox', 'sent', 'drafts', 'starred', 'important', 'task', 'archive', 'trash');
    }

    public function markRead(int $mailId, int $userId): void
    {
        $row = InternalMailRecipient::query()
            ->where('mail_id', $mailId)
            ->where('recipient_id', $userId)
            ->first();

        if ($row && ! $row->is_read) {
            $row->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    // -------------------------------------------------------------------------
    // Private folder queries
    // -------------------------------------------------------------------------

    private function inbox(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('trashed', false)
            ->where('archived', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                if (! empty($filters['importance'])) {
                    $q->where('importance', $filters['importance']);
                }
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function sent(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMail::query()
            ->with(['recipients.recipient:id,name'])
            ->where('sender_id', $uid)
            ->where('is_draft', false);

        if (! empty($filters['importance'])) {
            $query->where('importance', $filters['importance']);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function drafts(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        return InternalMail::query()
            ->where('sender_id', $uid)
            ->where('is_draft', true)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function starred(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('starred', true)
            ->where('trashed', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                if (! empty($filters['importance'])) {
                    $q->where('importance', $filters['importance']);
                }
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function important(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('trashed', false)
            ->where('archived', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false)->where('importance', '!=', 'normal');
                if (! empty($filters['importance'])) {
                    $q->where('importance', $filters['importance']);
                }
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function task(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('is_task', true)
            ->where('trashed', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                if (! empty($filters['importance'])) {
                    $q->where('importance', $filters['importance']);
                }
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function archive(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('archived', true)
            ->where('trashed', false)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                if (! empty($filters['importance'])) {
                    $q->where('importance', $filters['importance']);
                }
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    private function trash(int $uid, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InternalMailRecipient::query()
            ->with(['mail.sender:id,name'])
            ->where('recipient_id', $uid)
            ->where('trashed', true)
            ->whereHas('mail', function ($q) use ($filters) {
                $q->where('is_draft', false);
                if (! empty($filters['importance'])) {
                    $q->where('importance', $filters['importance']);
                }
            });

        if (! empty($filters['unread'])) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }
}
