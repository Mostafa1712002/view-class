<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\Promotion\Support\StandardGrades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * One-time guided backfill (decisions.md #3): the admin assigns a standard
 * `grade_number` to every legacy grade and a `number` to every legacy class so
 * the school becomes promotion-ready. No silent auto-mapping — the admin
 * confirms each value, and collisions are surfaced instead of being allowed.
 */
class SchoolStandardizationController extends Controller
{
    public function index(School $school)
    {
        $sections = $this->scopedSections($school);
        $standardGrades = StandardGrades::all();

        // Warn about grades that would collide once mapped (same ordinal), and
        // classes missing a number, so the admin resolves them before promotion.
        $collisions = self::gradeCollisions($sections);

        return view('admin.schools.standardization.index', compact(
            'school', 'sections', 'standardGrades', 'collisions'
        ));
    }

    public function save(Request $request, School $school)
    {
        $validated = $request->validate([
            'grade_number' => ['array'],
            'grade_number.*' => ['nullable', 'integer', Rule::in(array_keys(StandardGrades::all()))],
            'class_number' => ['array'],
            'class_number.*' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sections = $this->scopedSections($school);
        $sectionIds = $sections->pluck('id')->all();
        $classById = $sections->flatMap->classes->keyBy('id');

        // Desired grade ordinals = current, overridden by submitted (school-scoped).
        $desiredGrade = [];
        foreach ($sections as $section) {
            $desiredGrade[$section->id] = $section->grade_number;
        }
        foreach ($validated['grade_number'] ?? [] as $sid => $ord) {
            if (in_array((int) $sid, $sectionIds, true) && $ord !== null && $ord !== '') {
                $desiredGrade[(int) $sid] = (int) $ord;
            }
        }

        // Reject duplicate ordinals within the school (gender-agnostic, card #C1).
        $dupeGrades = collect($desiredGrade)->filter()->duplicates()->values();
        if ($dupeGrades->isNotEmpty()) {
            $labels = $dupeGrades->map(fn ($o) => StandardGrades::name($o))->implode('، ');

            return back()->with('error', __('schools.standardize_grade_collision', ['grades' => $labels]));
        }

        // Desired class numbers, grouped by section, checked for per-grade dupes.
        $desiredClass = [];
        foreach ($classById as $cid => $class) {
            $desiredClass[$cid] = $class->number;
        }
        foreach ($validated['class_number'] ?? [] as $cid => $num) {
            if ($classById->has((int) $cid) && $num !== null && $num !== '') {
                $desiredClass[(int) $cid] = (int) $num;
            }
        }
        foreach ($sections as $section) {
            $nums = $section->classes
                ->pluck('id')
                ->map(fn ($cid) => $desiredClass[$cid] ?? null)
                ->filter();
            if ($nums->duplicates()->isNotEmpty()) {
                return back()->with('error', __('schools.standardize_class_collision', ['grade' => $section->name]));
            }
        }

        DB::transaction(function () use ($sections, $classById, $desiredGrade, $desiredClass) {
            foreach ($sections as $section) {
                $ord = $desiredGrade[$section->id] ?? null;
                if ($ord && $ord !== $section->grade_number) {
                    // Assigning a standard ordinal also syncs the canonical name + level.
                    $grade = StandardGrades::get($ord);
                    $section->update([
                        'grade_number' => $ord,
                        'name' => $grade['name'],
                        'level' => $grade['level'],
                    ]);
                }
            }
            foreach ($classById as $cid => $class) {
                $num = $desiredClass[$cid] ?? null;
                if ($num !== $class->number) {
                    $class->update(['number' => $num]);
                }
            }
        });

        return redirect()
            ->route('admin.schools.standardization.index', $school)
            ->with('success', __('common.updated_successfully'));
    }

    /** @return \Illuminate\Support\Collection<int,\App\Models\Section> */
    private function scopedSections(School $school)
    {
        return $school->sections()
            ->with(['classes' => fn ($q) => $q->orderBy('number')->orderBy('name')])
            ->orderBy('grade_number')
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }

    /**
     * Ordinals currently assigned to more than one grade in the school.
     *
     * @param  \Illuminate\Support\Collection  $sections
     * @return array<int,int>
     */
    private static function gradeCollisions($sections): array
    {
        return $sections->pluck('grade_number')->filter()->duplicates()->values()->all();
    }

    /**
     * A school is promotion-ready once every grade has an ordinal and every
     * class has a number. Phase 5 (promotion engine) blocks execution until true.
     */
    public static function isReady(School $school): bool
    {
        if ($school->sections()->whereNull('grade_number')->exists()) {
            return false;
        }

        return ! \App\Models\ClassRoom::whereHas('section', fn ($q) => $q->where('school_id', $school->id))
            ->whereNull('number')
            ->exists();
    }
}
