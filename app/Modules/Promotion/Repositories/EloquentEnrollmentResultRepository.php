<?php

namespace App\Modules\Promotion\Repositories;

use App\Models\ClassRoom;
use App\Modules\Promotion\Repositories\Contracts\EnrollmentResultRepository;
use Illuminate\Support\Facades\DB;

class EloquentEnrollmentResultRepository implements EnrollmentResultRepository
{
    private const RESULTS = ['pending', 'passed', 'failed'];

    public function findScopedClass(int $classId, int $schoolId): ?ClassRoom
    {
        return ClassRoom::whereKey($classId)
            ->whereHas('section', fn ($s) => $s->where('school_id', $schoolId))
            ->first();
    }

    public function rosterStudents(ClassRoom $class)
    {
        return $class->students()
            ->orderByRaw('COALESCE(users.name_ar, users.name)')
            ->get();
    }

    public function setResults(int $classId, int $schoolId, array $studentResults): int
    {
        $updated = 0;

        foreach ($studentResults as $studentId => $result) {
            if (! in_array($result, self::RESULTS, true)) {
                continue;
            }

            $updated += $this->scopedPivot($classId, $schoolId)
                ->where('student_id', (int) $studentId)
                ->update(['result' => $result, 'updated_at' => now()]);
        }

        return $updated;
    }

    public function markAllPassed(int $classId, int $schoolId): int
    {
        return $this->scopedPivot($classId, $schoolId)
            ->update(['result' => 'passed', 'updated_at' => now()]);
    }

    /**
     * Pivot query fixed to one class AND re-verified to belong to $schoolId, so
     * no update can ever reach another tenant's enrollment.
     */
    private function scopedPivot(int $classId, int $schoolId)
    {
        return DB::table('class_student')
            ->where('class_id', $classId)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('classes')
                ->join('sections', 'sections.id', '=', 'classes.section_id')
                ->whereColumn('classes.id', 'class_student.class_id')
                ->where('sections.school_id', $schoolId));
    }
}
