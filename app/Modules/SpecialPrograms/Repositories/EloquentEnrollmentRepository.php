<?php

namespace App\Modules\SpecialPrograms\Repositories;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use Illuminate\Database\Eloquent\Builder;

class EloquentEnrollmentRepository implements EnrollmentRepository
{
    public function enrolledQuery(int $schoolId, string $program): Builder
    {
        return User::query()
            ->join('sp_enrollments as spe', 'spe.student_id', '=', 'users.id')
            ->where('spe.school_id', $schoolId)->where('spe.program', $program)->whereNull('spe.deleted_at')
            ->select('users.*', 'spe.id as enrollment_id', 'spe.group_id', 'spe.enrolled_at');
    }

    public function isEnrolled(int $studentId, string $program): bool
    {
        return SpEnrollment::where('student_id', $studentId)->where('program', $program)->exists();
    }

    public function programsOf(int $studentId): array
    {
        return SpEnrollment::where('student_id', $studentId)->pluck('program')->unique()->values()->all();
    }

    public function enroll(int $schoolId, string $program, array $studentIds, ?int $groupId, int $byUserId): int
    {
        $count = 0;
        foreach (array_unique(array_map('intval', $studentIds)) as $studentId) {
            $row = SpEnrollment::withTrashed()->firstOrNew(['school_id' => $schoolId, 'program' => $program, 'student_id' => $studentId]);
            if ($row->exists && ! $row->trashed()) {
                continue; // already enrolled
            }
            if ($row->trashed()) {
                $row->restore();
            }
            $row->fill(['group_id' => $groupId, 'enrolled_by' => $byUserId, 'enrolled_at' => now()])->save();
            $count++;
        }

        return $count;
    }

    public function remove(int $schoolId, string $program, int $studentId): void
    {
        SpEnrollment::where(['school_id' => $schoolId, 'program' => $program, 'student_id' => $studentId])->delete();
    }
}
