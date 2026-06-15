<?php

namespace App\Modules\Attendance\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read-side helper for student attendance screens (#261 / #262 / #263).
 *
 * The `attendances` table has no `school_id` column, so school scope is
 * derived through the `classes -> sections.school_id` join. A null
 * $schoolId means "see all" and is only ever passed for super-admins
 * (callers resolve it via scopedSchoolId(), which fail-closes others).
 */
class AttendanceQueryService
{
    /**
     * Classes the current scope may see (for filter dropdowns).
     *
     * @return Collection<int, ClassRoom>
     */
    public function classesForScope(?int $schoolId): Collection
    {
        return ClassRoom::query()
            ->with('section')
            ->when($schoolId !== null, function (Builder $q) use ($schoolId) {
                $q->whereHas('section', fn (Builder $s) => $s->where('school_id', $schoolId));
            })
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Subject>
     */
    public function subjectsForScope(?int $schoolId): Collection
    {
        return Subject::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();
    }

    public function currentAcademicYearId(): ?int
    {
        return AcademicYear::where('is_current', true)->value('id')
            ?? AcademicYear::orderByDesc('start_date')->value('id');
    }

    /**
     * Students of a class, scope-checked. Returns empty when the class is
     * outside the caller's school scope.
     *
     * @return Collection<int, User>
     */
    public function studentsForClass(int $classId, ?int $schoolId, array $filters = []): Collection
    {
        $class = ClassRoom::with('section')->find($classId);
        if (! $class) {
            return new Collection();
        }
        if ($schoolId !== null && (int) optional($class->section)->school_id !== $schoolId) {
            return new Collection();
        }

        return $class->students()
            ->when(! empty($filters['name']), fn ($q) => $q->where('users.name', 'like', '%'.$filters['name'].'%'))
            ->when(! empty($filters['national_id']), fn ($q) => $q->where('users.national_id', 'like', '%'.$filters['national_id'].'%'))
            ->orderBy('users.name')
            ->get();
    }

    /**
     * Existing attendance rows keyed by student_id for a class/date(/period).
     *
     * @return array<int, Attendance>
     */
    public function existingRows(int $classId, string $date, ?int $period): array
    {
        return Attendance::query()
            ->where('class_id', $classId)
            ->whereDate('date', $date)
            ->when($period !== null, fn ($q) => $q->where('period', $period), fn ($q) => $q->whereNull('period'))
            ->get()
            ->keyBy('student_id')
            ->all();
    }

    /**
     * Aggregate status counts for the stat cards.
     *
     * @return array{present:int,absent:int,late:int,excused:int}
     */
    public function statusCounts(int $classId, string $date, ?int $period): array
    {
        $rows = Attendance::query()
            ->where('class_id', $classId)
            ->whereDate('date', $date)
            ->when($period !== null, fn ($q) => $q->where('period', $period), fn ($q) => $q->whereNull('period'))
            ->selectRaw('status, COUNT(*) c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        return [
            'present' => (int) ($rows['present'] ?? 0),
            'absent'  => (int) ($rows['absent'] ?? 0),
            'late'    => (int) ($rows['late'] ?? 0),
            'excused' => (int) ($rows['excused'] ?? 0),
        ];
    }

    /**
     * Lifetime present-day count for a student (for the "عدد أيام الحضور" column).
     */
    public function presentDaysCount(int $studentId): int
    {
        return Attendance::where('student_id', $studentId)
            ->whereNull('period')
            ->where('status', 'present')
            ->distinct('date')
            ->count('date');
    }

    /**
     * Verify a class is inside the caller's school scope.
     */
    public function classInScope(ClassRoom $class, ?int $schoolId): bool
    {
        if ($schoolId === null) {
            return true;
        }

        return (int) optional($class->section)->school_id === $schoolId;
    }
}
