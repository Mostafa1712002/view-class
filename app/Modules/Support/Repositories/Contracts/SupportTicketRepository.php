<?php

namespace App\Modules\Support\Repositories\Contracts;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SupportTicketRepository
{
    /**
     * Get tickets created by a specific user within a school.
     */
    public function getUserTickets(int $schoolId, int $userId): LengthAwarePaginator;

    /**
     * Get all tickets for a school with optional filters (admin view).
     *
     * @param array $filters Optional: status, priority, category
     */
    public function getSchoolTickets(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a ticket by ID (no scope — caller applies scope logic).
     */
    public function find(int $id): ?SupportTicket;

    /**
     * Create a new support ticket.
     */
    public function create(array $data): SupportTicket;

    /**
     * Add a reply to a ticket and update last_reply_at.
     */
    public function addReply(int $ticketId, array $data): SupportTicketReply;

    /**
     * Assign a ticket to a staff user.
     */
    public function assign(int $ticketId, int $userId): SupportTicket;

    /**
     * Update the status of a ticket.
     */
    public function updateStatus(int $ticketId, string $status): SupportTicket;
}
