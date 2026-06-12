<?php

namespace App\Modules\Appointments\Repositories;

use App\Models\Appointment;
use App\Modules\Appointments\Repositories\Contracts\AppointmentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class EloquentAppointmentRepository implements AppointmentRepository
{
    public function myBookings(int $userId, bool $asParent, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $q = Appointment::query()
            ->with(['bookableRole', 'targetUser', 'student', 'subject'])
            ->when($asParent,
                fn ($q) => $q->where('booked_by', $userId),
                fn ($q) => $q->where('student_id', $userId)
            );

        $this->applyCommonFilters($q, $filters);

        return $q->orderByDesc('appointment_date')->orderByDesc('appointment_time')->paginate($perPage);
    }

    public function forStaff(int $staffId, ?int $schoolId, bool $isAdmin, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $q = Appointment::query()
            ->with(['bookableRole', 'targetUser', 'student', 'bookedBy', 'subject'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $isAdmin, fn ($q) => $q->where('target_user_id', $staffId));

        $this->applyCommonFilters($q, $filters);

        if (! empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->whereHas('student', fn ($s) => $s->where('name', 'like', $term));
        }

        return $q->orderByDesc('appointment_date')->orderByDesc('appointment_time')->paginate($perPage);
    }

    public function find(int $id): ?Appointment
    {
        return Appointment::with(['bookableRole', 'targetUser', 'student', 'bookedBy', 'subject', 'decidedBy'])->find($id);
    }

    public function create(array $payload): Appointment
    {
        return Appointment::create($payload);
    }

    public function decide(int $id, string $status, int $decidedBy, ?string $decisionNote = null): Appointment
    {
        $appt = Appointment::findOrFail($id);
        $appt->update([
            'status'        => $status,
            'decision_by'   => $decidedBy,
            'decision_at'   => Carbon::now(),
            'decision_note' => $decisionNote,
        ]);

        return $appt->fresh();
    }

    public function cancel(int $id, int $userId): bool
    {
        $appt = Appointment::where('id', $id)
            ->where('booked_by', $userId)
            ->whereIn('status', ['requested', 'confirmed'])
            ->first();

        if (! $appt) {
            return false;
        }

        $appt->update(['status' => 'cancelled']);

        return true;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function applyCommonFilters($q, array $filters): void
    {
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $q->where('appointment_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $q->where('appointment_date', '<=', $filters['date_to']);
        }
    }
}
