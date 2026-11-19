<?php

namespace App\Modules\SpecialPrograms\Repositories;

use App\Modules\SpecialPrograms\Models\SpGroup;
use App\Modules\SpecialPrograms\Models\SpTeacher;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use Illuminate\Support\Collection;

final class EloquentGroupRepository implements GroupRepository
{
    public function forSchool(int $schoolId, string $program, ?int $teacherId = null): Collection
    {
        return SpGroup::where('school_id', $schoolId)->where('program', $program)
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->withCount('enrollments')->orderBy('name')->get();
    }

    public function groupIdsLedBy(int $teacherId, string $program): array
    {
        return SpGroup::where('teacher_id', $teacherId)->where('program', $program)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function teacherSubjectIds(int $schoolId, int $teacherId, string $program): ?array
    {
        $ids = SpTeacher::where('school_id', $schoolId)->where('teacher_id', $teacherId)->where('program', $program)
            ->pluck('sp_subject_id');

        return $ids->contains(null) ? null : $ids->map(fn ($id) => (int) $id)->values()->all();
    }
}
