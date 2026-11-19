<?php

namespace App\Modules\SpecialPrograms\Support;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\User;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use Illuminate\Support\Collection;

/** Eligible classes and readiness alerts for a school/program (D§6). */
final class ProgramAudience
{
    /** Grade number of a class: its own grade_level, else the section's grade_number; null when unnumbered. */
    public static function gradeOf(ClassRoom $class): ?int
    {
        $grade = (int) $class->grade_level ?: (int) ($class->section?->grade_number ?? 0);

        return $grade > 0 ? $grade : null;
    }

    /**
     * Classes of the school's current academic year (all years when none is flagged current).
     * Each row gets `grade` and `eligible` (grade in the program's grades). $all keeps ineligible rows.
     *
     * @return Collection<int, ClassRoom>
     */
    public static function classes(int $schoolId, string $program, bool $all = false): Collection
    {
        $yearIds = AcademicYear::where('school_id', $schoolId)->where('is_current', true)->pluck('id');
        // the super-admin «الصفوف المستهدفة» setting, falling back to the program defaults
        $grades = app(SettingsRepository::class)->gradeLevels($program);

        $classes = ClassRoom::with('section')
            ->whereHas('section', fn ($q) => $q->where('school_id', $schoolId))
            ->when($yearIds->isNotEmpty(), fn ($q) => $q->whereIn('academic_year_id', $yearIds))
            ->orderBy('section_id')->orderBy('name')->get()
            ->each(function (ClassRoom $c) use ($grades) {
                $c->setAttribute('grade', self::gradeOf($c));
                $c->setAttribute('eligible', $c->grade !== null && in_array($c->grade, $grades, true));
            });

        return $all ? $classes->values() : $classes->where('eligible', true)->values();
    }

    /** @return array{unnumbered: Collection, none_eligible: bool} */
    public static function alerts(int $schoolId, string $program): array
    {
        $classes = self::classes($schoolId, $program, true);

        return [
            'unnumbered' => $classes->whereNull('grade')->values(),
            'none_eligible' => $classes->where('eligible', true)->isEmpty(),
        ];
    }

    /** Students of a school, optionally limited to classes (users.class_room_id or the class_student pivot). */
    public static function studentsQuery(int $schoolId, array $classIds = [])
    {
        return User::query()->where('users.school_id', $schoolId)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->when($classIds !== [], fn ($q) => $q->where(fn ($w) => $w->whereIn('class_room_id', $classIds)
                ->orWhereHas('enrolledClasses', fn ($c) => $c->whereIn('classes.id', $classIds))));
    }
}
