<?php

namespace App\Modules\VirtualClasses\Repositories;

use App\Models\VirtualClass;
use App\Modules\VirtualClasses\Models\VirtualClassAttendee;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VirtualClassRepository implements VirtualClassRepositoryInterface
{
    public function forStaff(int $userId, int $schoolId, bool $roleIsAdmin, string $tab = 'all', int $perPage = 20): LengthAwarePaginator
    {
        $query = VirtualClass::query()
            ->where('school_id', $schoolId)
            ->with(['teacher:id,name,name_ar', 'creator:id,name,name_ar', 'classRoom:id,name', 'subject:id,name']);

        if (! $roleIsAdmin) {
            // Teacher scope: only sessions they teach.
            $query->where('teacher_id', $userId);
        }

        $this->applyTab($query, $tab);

        $order = $tab === 'today' ? 'asc' : 'desc';

        return $query->orderBy('scheduled_at', $order)->paginate($perPage)->withQueryString();
    }

    /**
     * Tabs (card spec):
     *  - today    : scheduled for today, not cancelled.
     *  - recorded : has a recording link (join_url/external_url) — "الفصول المسجلة".
     *  - old      : scheduled_at in the past OR ended.
     *  - all      : no extra filter.
     */
    private function applyTab($query, string $tab): void
    {
        switch ($tab) {
            case 'today':
                $query->whereDate('scheduled_at', today())
                      ->where('status', '!=', 'cancelled');
                break;
            case 'recorded':
                $query->where('status', 'ended')
                      ->where(function ($q) {
                          $q->whereNotNull('join_url')->orWhereNotNull('external_url');
                      });
                break;
            case 'old':
                $query->where(function ($q) {
                    $q->where('scheduled_at', '<', now())->orWhere('status', 'ended');
                });
                break;
            case 'all':
            default:
                // no filter
                break;
        }
    }

    public function forStudent(int $userId, int $schoolId, int $perPage = 20): LengthAwarePaginator
    {
        $classIds = DB::table('class_student')
            ->where('student_id', $userId)
            ->pluck('class_id')
            ->all();

        return VirtualClass::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', ['scheduled', 'live'])
            ->where(function ($q) {
                $q->whereJsonContains('audience', 'all')
                  ->orWhereJsonContains('audience', 'students');
            })
            ->where(function ($q) use ($classIds) {
                $q->whereNull('class_id');
                if (! empty($classIds)) {
                    $q->orWhereIn('class_id', $classIds);
                }
            })
            ->with(['teacher:id,name,name_ar', 'subject:id,name'])
            ->orderBy('scheduled_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id, int $schoolId): ?VirtualClass
    {
        return VirtualClass::with([
            'teacher:id,name,name_ar',
            'creator:id,name,name_ar',
            'classRoom:id,name',
            'subject:id,name',
        ])
            ->where('school_id', $schoolId)
            ->find($id);
    }

    public function create(array $data): VirtualClass
    {
        return VirtualClass::create($data);
    }

    public function update(int $id, array $data): VirtualClass
    {
        $vc = VirtualClass::findOrFail($id);
        $vc->update($data);

        return $vc->fresh();
    }

    public function updateStatus(int $id, string $status): VirtualClass
    {
        $vc = VirtualClass::findOrFail($id);
        $vc->update(['status' => $status]);

        return $vc->fresh();
    }

    public function delete(int $id): void
    {
        VirtualClass::findOrFail($id)->delete();
    }

    public function recordEntry(int $virtualClassId, int $studentId, int $schoolId): VirtualClassAttendee
    {
        $attendee = VirtualClassAttendee::firstOrNew([
            'virtual_class_id' => $virtualClassId,
            'student_id'       => $studentId,
        ]);

        $attendee->school_id = $schoolId;
        if (! $attendee->joined_at) {
            $attendee->joined_at = now();
        }
        $attendee->save();

        return $attendee;
    }

    public function attendeesFor(int $virtualClassId): Collection
    {
        return VirtualClassAttendee::with(['student:id,name,name_ar'])
            ->where('virtual_class_id', $virtualClassId)
            ->orderByDesc('joined_at')
            ->get();
    }

    public function rosterStudentIds(?int $classId): array
    {
        if (! $classId) {
            return [];
        }

        return DB::table('class_student')
            ->where('class_id', $classId)
            ->pluck('student_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
