<?php

namespace App\Modules\Support\Repositories\Contracts;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\SupportTicketStatusLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupportTicketRepository
{
    /**
     * Get tickets created by a specific user within a school.
     * A null $schoolId means unscoped (super-admin only — caller enforces).
     */
    public function getUserTickets(?int $schoolId, int $userId): LengthAwarePaginator;

    /**
     * Get tickets for a school with optional filters (admin view).
     * A null $schoolId means unscoped (super-admin sees every school).
     *
     * @param array $filters Optional: status, priority, category, type, department, reply_state
     */
    public function getSchoolTickets(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Status / reply-state counters for the admin stat cards, school-scoped.
     * A null $schoolId means unscoped (super-admin).
     *
     * @return array<string,int>
     */
    public function adminCounts(?int $schoolId): array;

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
     * Update the status of a ticket and write a status-change log row.
     */
    public function updateStatus(int $ticketId, string $status, ?int $byUserId = null): SupportTicket;

    /**
     * Soft-delete a ticket.
     */
    public function delete(int $ticketId): void;
}
