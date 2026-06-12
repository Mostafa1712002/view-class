<?php

namespace App\Modules\Appointments\Repositories\Contracts;

use App\Models\AppointmentSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AppointmentScheduleRepository
{
    /**
     * Paginate schedules scoped to school + optional owner.
     * Teachers see only their own; admins see all for the school.
     *
     * @param int|null $schoolId Active school id
     * @param int|null $ownerId  When non-null, restrict to this owner (teacher scope)
     * @param array    $filters  Optional: q, status, mode, date_from, date_to
     * @param int      $perPage
     */
    public function paginate(?int $schoolId, ?int $ownerId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a schedule by id, scoped to school (and optionally owner).
     * Returns null if not found / out of scope.
     */
    public function findScoped(int $id, ?int $schoolId, ?int $ownerId = null): ?AppointmentSchedule;

    /**
     * Create a new schedule.
     */
    public function create(array $payload): AppointmentSchedule;

    /**
     * Update an existing schedule.
     */
    public function update(AppointmentSchedule $schedule, array $payload): AppointmentSchedule;

    /**
     * Soft-delete a schedule.
     */
    public function delete(AppointmentSchedule $schedule): void;

    /**
     * Toggle the booking_open flag.
     */
    public function toggleBookingOpen(AppointmentSchedule $schedule): AppointmentSchedule;

    /**
     * Duplicate a schedule (copy with reset booked state).
     */
    public function copy(AppointmentSchedule $schedule, int $createdBy): AppointmentSchedule;
}
