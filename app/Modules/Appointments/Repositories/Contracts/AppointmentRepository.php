<?php

namespace App\Modules\Appointments\Repositories\Contracts;

use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AppointmentRepository
{
    /**
     * Paginate the current user's bookings.
     *
     * @param int  $userId    The auth user id (student_id or booked_by).
     * @param bool $asParent  If true, query by booked_by; if false, by student_id.
     * @param array $filters  Optional: status, date_from, date_to
     */
    public function myBookings(int $userId, bool $asParent, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Paginate bookings for a staff member's management view.
     *
     * @param int  $staffId   The staff user's id (target_user_id).
     * @param int|null $schoolId School scope.
     * @param bool $isAdmin   If true, show all school bookings (not just directed at $staffId).
     * @param array $filters  Optional: status, date_from, date_to, q
     */
    public function forStaff(int $staffId, ?int $schoolId, bool $isAdmin, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a single booking by id.
     */
    public function find(int $id): ?Appointment;

    /**
     * Create a new booking.
     */
    public function create(array $payload): Appointment;

    /**
     * Set status to the given decision value; record decision metadata.
     * Returns the updated model.
     */
    public function decide(int $id, string $status, int $decidedBy, ?string $decisionNote = null): Appointment;

    /**
     * Cancel a booking (set status='cancelled').
     * Only the original booker may cancel; only while status is requested|confirmed.
     * Returns true on success; false if the record was not found / not cancellable.
     */
    public function cancel(int $id, int $userId): bool;
}
