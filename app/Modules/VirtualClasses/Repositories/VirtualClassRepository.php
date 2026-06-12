<?php

namespace App\Modules\VirtualClasses\Repositories;

use App\Models\VirtualClass;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VirtualClassRepository implements VirtualClassRepositoryInterface
{
    public function forStaff(int $userId, int $schoolId, bool $roleIsAdmin, int $perPage = 20): LengthAwarePaginator
    {
        $query = VirtualClass::query()
            ->where('school_id', $schoolId)
            ->with(['teacher:id,name,name_ar', 'creator:id,name,name_ar']);

        if (! $roleIsAdmin) {
            $query->where('teacher_id', $userId);
        }

        return $query->orderByDesc('scheduled_at')->paginate($perPage)->withQueryString();
    }

    public function forStudent(int $userId, int $schoolId, int $perPage = 20): LengthAwarePaginator
    {
        // Classes the student is enrolled in (to scope class-targeted sessions).
        $classIds = \Illuminate\Support\Facades\DB::table('class_student')
            ->where('student_id', $userId)
            ->pluck('class_id')
            ->all();

        return VirtualClass::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', ['scheduled', 'live'])
            // Audience must target students (or everyone).
            ->where(function ($q) {
                $q->whereJsonContains('audience', 'all')
                  ->orWhereJsonContains('audience', 'students');
            })
            // Class-targeted sessions only show to students in that class; school-wide
            // sessions (class_id NULL) show to all.
            ->where(function ($q) use ($classIds) {
                $q->whereNull('class_id');
                if (! empty($classIds)) {
                    $q->orWhereIn('class_id', $classIds);
                }
            })
            ->with(['teacher:id,name,name_ar'])
            ->orderBy('scheduled_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id, int $schoolId): ?VirtualClass
    {
        return VirtualClass::with(['teacher:id,name,name_ar', 'creator:id,name,name_ar'])
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
}
