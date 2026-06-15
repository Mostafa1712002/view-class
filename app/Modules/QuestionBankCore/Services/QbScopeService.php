<?php

namespace App\Modules\QuestionBankCore\Services;

use App\Models\AcademicTerm;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\StudyWeek;
use App\Models\Subject;
use App\Modules\QuestionBankCore\Models\Compound;
use App\Modules\QuestionBankCore\Models\Skill;
use Illuminate\Support\Collection;

/**
 * Resolves the cascading taxonomy for the school/grade selector (#249) and the
 * classification dropdowns reused by #250/#253.
 *
 * Everything is school-scoped: pass the caller's scopedSchoolId() (null only for
 * a super-admin = see all). grade-level is resolved from classes.grade_level
 * (the gradebook `grades` table is unrelated — see foundation design.md).
 */
class QbScopeService
{
    /**
     * Schools the user may target, grouped under their compound. Schools not in
     * any compound are returned under a synthetic null-keyed "ungrouped" group so
     * the selector renders even when no compounds exist (degenerate-data safe).
     *
     * @return array{compounds: \Illuminate\Support\Collection, ungrouped: \Illuminate\Support\Collection}
     */
    public function schoolTree(?int $schoolId): array
    {
        $schools = $this->schoolsInScope($schoolId);

        $compoundIds = \DB::table('compound_school')
            ->whereIn('school_id', $schools->pluck('id'))
            ->pluck('compound_id', 'school_id'); // school_id => compound_id

        $compounds = Compound::query()
            ->whereIn('id', $compoundIds->values()->unique())
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        $grouped = collect();
        $ungrouped = collect();

        foreach ($schools as $school) {
            $cid = $compoundIds[$school->id] ?? null;
            if ($cid !== null && $compounds->has($cid)) {
                $school->setAttribute('compound_id', $cid);
                $grouped->push($school);
            } else {
                $ungrouped->push($school);
            }
        }

        $compoundGroups = $grouped
            ->groupBy('compound_id')
            ->map(fn (Collection $items, $cid) => [
                'compound' => $compounds->get($cid),
                'schools'  => $items->values(),
            ])
            ->values();

        return ['compounds' => $compoundGroups, 'ungrouped' => $ungrouped->values()];
    }

    /**
     * Schools the user may target. A school-scoped user sees only their school;
     * a super-admin (null) sees every active school.
     *
     * @return \Illuminate\Support\Collection<int,School>
     */
    public function schoolsInScope(?int $schoolId): Collection
    {
        $query = School::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($schoolId !== null) {
            $query->whereKey($schoolId);
        }

        return $query->get(['id', 'name', 'name_ar', 'name_en', 'student_gender', 'stage', 'educational_company_id']);
    }

    /**
     * Distinct grade-levels (from classes.grade_level) available in a school.
     *
     * @return \Illuminate\Support\Collection<int,int>
     */
    public function gradeLevelsForSchool(int $schoolId): Collection
    {
        return ClassRoom::query()
            ->whereHas('section', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');
    }

    /**
     * Classes within a school, optionally narrowed to one grade-level.
     *
     * @return \Illuminate\Support\Collection<int,Classes>
     */
    public function classesForSchool(int $schoolId, ?int $gradeLevel = null): Collection
    {
        return ClassRoom::query()
            ->whereHas('section', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when($gradeLevel !== null, fn ($q) => $q->where('grade_level', $gradeLevel))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'division']);
    }

    /**
     * Academic terms (semesters / الفصل الدراسي) within a school's academic years.
     *
     * @return \Illuminate\Support\Collection<int,AcademicTerm>
     */
    public function semestersForSchool(int $schoolId): Collection
    {
        return AcademicTerm::query()
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'academic_year_id']);
    }

    /**
     * Study weeks for a given semester (academic term).
     *
     * @return \Illuminate\Support\Collection<int,StudyWeek>
     */
    public function weeksForSemester(int $semesterId): Collection
    {
        return StudyWeek::query()
            ->where('academic_term_id', $semesterId)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'academic_term_id']);
    }

    /**
     * Subjects within a school (plus global/null-school subjects).
     *
     * @return \Illuminate\Support\Collection<int,Subject>
     */
    public function subjectsForSchool(?int $schoolId): Collection
    {
        return Subject::query()
            ->where('is_active', true)
            ->when($schoolId !== null, fn ($q) => $q->where(
                fn ($w) => $w->where('school_id', $schoolId)->orWhereNull('school_id')
            ))
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'school_id']);
    }

    /**
     * Skills within a school (plus global), optionally narrowed by subject.
     *
     * @return \Illuminate\Support\Collection<int,Skill>
     */
    public function skillsForSchool(?int $schoolId, ?int $subjectId = null): Collection
    {
        return Skill::query()
            ->when($schoolId !== null, fn ($q) => $q->where(
                fn ($w) => $w->where('school_id', $schoolId)->orWhereNull('school_id')
            ))
            ->when($subjectId !== null, fn ($q) => $q->where('subject_id', $subjectId))
            ->orderBy('name')
            ->get(['id', 'name', 'subject_id']);
    }

    /**
     * Human label for a school's student-gender (school type: بنين / بنات / مشترك).
     */
    public function schoolTypeLabel(?string $gender): string
    {
        return match ($gender) {
            'boys'  => 'بنين',
            'girls' => 'بنات',
            default => 'مشترك',
        };
    }
}
