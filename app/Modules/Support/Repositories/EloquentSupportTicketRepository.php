<?php

namespace App\Modules\Support\Repositories;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Modules\Support\Repositories\Contracts\SupportTicketRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentSupportTicketRepository implements SupportTicketRepository
{
    public function getUserTickets(int $schoolId, int $userId): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->where('school_id', $schoolId)
            ->where('created_by', $userId)
            ->with(['creator:id,name,name_ar', 'assignee:id,name,name_ar'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function getSchoolTickets(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SupportTicket::query()
            ->where('school_id', $schoolId)
            ->with(['creator:id,name,name_ar', 'assignee:id,name,name_ar']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?SupportTicket
    {
        return SupportTicket::with([
            'creator:id,name,name_ar',
            'assignee:id,name,name_ar',
            'replies.user:id,name,name_ar',
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

    public function updateStatus(int $ticketId, string $status): SupportTicket
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update(['status' => $status]);

        return $ticket->fresh();
    }
}
