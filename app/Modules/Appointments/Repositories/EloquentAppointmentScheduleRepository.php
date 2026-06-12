<?php

namespace App\Modules\Appointments\Repositories;

use App\Models\AppointmentSchedule;
use App\Modules\Appointments\Repositories\Contracts\AppointmentScheduleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAppointmentScheduleRepository implements AppointmentScheduleRepository
{
    // ─── Internal helpers ─────────────────────────────────────────────────────

    /**
     * Base query: always school-scoped; optionally owner-scoped (teacher).
     */
    private function baseQuery(?int $schoolId, ?int $ownerId = null)
    {
        $query = AppointmentSchedule::query()->withTrashed(false);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Teacher scope: only see own schedules
        if ($ownerId !== null) {
            $query->where('owner_id', $ownerId);
        }

        return $query;
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    public function paginate(?int $schoolId, ?int $ownerId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseQuery($schoolId, $ownerId)
            ->with(['owner:id,name,name_ar', 'school:id,name,name_en']);

        if (! empty($filters['q'])) {
            $needle = '%' . $filters['q'] . '%';
            $query->where('title', 'like', $needle);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['mode'])) {
            $query->where('mode', $filters['mode']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('date_from', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('date_to', '<=', $filters['date_to']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId, ?int $ownerId = null): ?AppointmentSchedule
    {
        return $this->baseQuery($schoolId, $ownerId)->find($id);
    }

    public function create(array $payload): AppointmentSchedule
    {
        return AppointmentSchedule::create($payload);
    }

    public function update(AppointmentSchedule $schedule, array $payload): AppointmentSchedule
    {
        $schedule->update($payload);
        return $schedule->fresh();
    }

    public function delete(AppointmentSchedule $schedule): void
    {
        $schedule->delete();
    }

    public function toggleBookingOpen(AppointmentSchedule $schedule): AppointmentSchedule
    {
        $schedule->update(['booking_open' => ! $schedule->booking_open]);
        return $schedule->fresh();
    }

    public function copy(AppointmentSchedule $schedule, int $createdBy): AppointmentSchedule
    {
        $attrs = $schedule->only([
            'school_id', 'owner_id', 'title', 'date_from', 'date_to',
            'days', 'time_from', 'time_to', 'slot_minutes', 'max_appointments',
            'location', 'mode', 'notes', 'status',
        ]);
        $attrs['title']      = $schedule->title . ' (نسخة)';
        $attrs['booking_open'] = 1;
        $attrs['created_by'] = $createdBy;
        $attrs['days']       = $schedule->days; // already cast to array → stored as json

        return AppointmentSchedule::create($attrs);
    }
}
