<?php

namespace App\Modules\Support\Repositories;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\SupportTicketStatusLog;
use App\Modules\Support\Repositories\Contracts\SupportTicketRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSupportTicketRepository implements SupportTicketRepository
{
    public function getUserTickets(?int $schoolId, int $userId): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->where('created_by', $userId)
            ->with(['creator:id,name,name_ar', 'assignee:id,name,name_ar', 'relatedStudent:id,name'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function getSchoolTickets(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SupportTicket::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->with([
                'creator:id,name,name_ar',
                'assignee:id,name,name_ar',
                'school:id,name',
                'replies:id,ticket_id,is_staff',
            ]);

        foreach (['status', 'priority', 'category', 'type', 'department'] as $f) {
            if (! empty($filters[$f])) {
                $query->where($f, $filters[$f]);
            }
        }

        // Derived "who replied last" filter (admin_replied / user_replied).
        if (! empty($filters['reply_state'])) {
            $wantStaff = $filters['reply_state'] === 'admin_replied' ? 1 : 0;
            $query->whereHas('replies', function ($q) use ($wantStaff) {
                $q->where('is_staff', $wantStaff)
                  ->whereRaw('support_ticket_replies.id = (
                      SELECT MAX(r2.id) FROM support_ticket_replies r2
                      WHERE r2.ticket_id = support_ticket_replies.ticket_id
                  )');
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function adminCounts(?int $schoolId): array
    {
        $base = SupportTicket::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId));

        $lastReplyStaff = function (bool $staff) use ($schoolId) {
            return SupportTicket::query()
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->whereHas('replies', function ($q) use ($staff) {
                    $q->where('is_staff', $staff ? 1 : 0)
                      ->whereRaw('support_ticket_replies.id = (
                          SELECT MAX(r2.id) FROM support_ticket_replies r2
                          WHERE r2.ticket_id = support_ticket_replies.ticket_id
                      )');
                })
                ->count();
        };

        return [
            'all'           => (clone $base)->count(),
            'open'          => (clone $base)->where('status', 'open')->count(),
            'in_progress'   => (clone $base)->where('status', 'in_progress')->count(),
            'resolved'      => (clone $base)->where('status', 'resolved')->count(),
            'closed'        => (clone $base)->where('status', 'closed')->count(),
            'admin_replied' => $lastReplyStaff(true),
            'user_replied'  => $lastReplyStaff(false),
        ];
    }

    public function find(int $id): ?SupportTicket
    {
        return SupportTicket::with([
            'creator:id,name,name_ar',
            'assignee:id,name,name_ar',
            'relatedStudent:id,name',
            'school:id,name',
            'replies.user:id,name,name_ar',
            'statusLogs.user:id,name,name_ar',
        ])->find($id);
    }

    public function create(array $data): SupportTicket
    {
        return SupportTicket::create($data);
    }

    public function addReply(int $ticketId, array $data): SupportTicketReply
    {
        $reply = SupportTicketReply::create(array_merge($data, ['ticket_id' => $ticketId]));

        SupportTicket::where('id', $ticketId)->update(['last_reply_at' => now()]);

        return $reply;
    }

    public function assign(int $ticketId, int $userId): SupportTicket
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update([
            'assigned_to'   => $userId,
            'last_reply_at' => now(),
        ]);

        return $ticket->fresh();
    }

    public function updateStatus(int $ticketId, string $status, ?int $byUserId = null): SupportTicket
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $from = $ticket->status;

        if ($from !== $status) {
            $ticket->update(['status' => $status]);

            SupportTicketStatusLog::create([
                'ticket_id'   => $ticket->id,
                'user_id'     => $byUserId,
                'from_status' => $from,
                'to_status'   => $status,
            ]);
        }

        return $ticket->fresh();
    }

    public function delete(int $ticketId): void
    {
        SupportTicket::where('id', $ticketId)->delete();
    }
}
