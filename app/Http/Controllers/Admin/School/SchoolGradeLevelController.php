<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Modules\Promotion\Support\StandardGrades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolGradeLevelController extends Controller
{
    public function index(School $school)
    {
        $sections = $school->sections()
            ->with(['classes' => fn($q) => $q->withCount('students')])
            ->orderBy('grade_number')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $standardGrades = StandardGrades::all();

        return view('admin.schools.grade_levels.index', compact('school', 'sections', 'standardGrades'));
    }

    public function storeSection(Request $request, School $school)
    {
        // Grades are picked from the fixed standard list (نوع الصف الدراسي). The
        // chosen ordinal drives name + level; gender stays on the class (card #C1).
        $validated = $request->validate([
            'grade_number' => ['required', 'integer', Rule::in(array_keys(StandardGrades::all()))],
        ]);
        $ordinal = (int) $validated['grade_number'];

        if ($this->gradeNumberTaken($school, $ordinal)) {
            return back()->with('error', __('schools.grade_number_taken'));
        }

        $grade = StandardGrades::get($ordinal);
        Section::create([
            'school_id' => $school->id,
            'name' => $grade['name'],
            'level' => $grade['level'],
            'grade_number' => $ordinal,
        ]);

        return back()->with('success', __('common.created_successfully'));
    }

    /**
     * Map an existing (legacy) grade to a standard entry — assigns the ordinal
     * and syncs name/level. Classes and students are left untouched.
     */
    public function updateSection(Request $request, School $school, Section $section)
    {
        abort_unless($section->school_id === $school->id, 404);

        $validated = $request->validate([
            'grade_number' => ['required', 'integer', Rule::in(array_keys(StandardGrades::all()))],
        ]);
        $ordinal = (int) $validated['grade_number'];

        if ($this->gradeNumberTaken($school, $ordinal, $section->id)) {
            return back()->with('error', __('schools.grade_number_taken'));
        }

        $grade = StandardGrades::get($ordinal);
        $section->update([
            'name' => $grade['name'],
            'level' => $grade['level'],
            'grade_number' => $ordinal,
        ]);

        return back()->with('success', __('common.updated_successfully'));
    }

    /** Duplicate ordinal guard — gender-agnostic, per school (card #C1). */
    private function gradeNumberTaken(School $school, int $ordinal, ?int $ignoreId = null): bool
    {
        return Section::where('school_id', $school->id)
            ->where('grade_number', $ordinal)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function showClasses(School $school, Section $section)
    {
        abort_unless($section->school_id === $school->id, 404);

        $classes = $section->classes()
            ->with(['leadTeacher', 'academicYear'])
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $academicYears = $school->academicYears()->orderByDesc('start_date')->get();
        $teachers = User::where('school_id', $school->id)
            ->whereHas('roles', fn($r) => $r->where('slug', 'teacher'))
            ->get();

        return view('admin.schools.grade_levels.classes', compact('school', 'section', 'classes', 'academicYears', 'teachers'));
    }

    public function storeClass(Request $request, School $school, Section $section)
    {
        abort_unless($section->school_id === $school->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer|min:1|max:12',
            'gender' => 'required|in:boys,girls,mixed',
            // رقم الفصل — unique within this grade (Rule #1).
            'number' => [
                'nullable', 'integer', 'min:1', 'max:100',
                Rule::unique('classes', 'number')->where(fn ($q) => $q
                    ->where('section_id', $section->id)
                    ->where('academic_year_id', $request->input('academic_year_id'))),
            ],
            'lead_teacher_id' => 'nullable|exists:users,id',
            'capacity' => 'required|integer|min:1|max:200',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);
        $validated['section_id'] = $section->id;

        ClassRoom::create($validated);

        return back()->with('success', __('common.created_successfully'));
    }

    public function destroyClass(School $school, Section $section, ClassRoom $class)
    {
        abort_unless(
            $section->school_id === $school->id && $class->section_id === $section->id,
            404
        );
        if ($class->students()->exists()) {
            return back()->with('error', __('schools.class_has_students'));
        }
        $class->delete();
        return back()->with('success', __('common.deleted_successfully'));
    }

    public function showClass(School $school, Section $section, ClassRoom $class)
    {
        abort_unless(
            $section->school_id === $school->id && $class->section_id === $section->id,
            404
        );

        $class->loadCount('students');
        $class->load(['leadTeacher', 'academicYear']);

        return view('admin.schools.grade_levels.class_show', compact('school', 'section', 'class'));
    }

    public function editClass(School $school, Section $section, ClassRoom $class)
    {
        abort_unless(
            $section->school_id === $school->id && $class->section_id === $section->id,
            404
        );

        $academicYears = $school->academicYears()->orderByDesc('start_date')->get();
        $teachers = User::where('school_id', $school->id)
            ->whereHas('roles', fn($r) => $r->where('slug', 'teacher'))
            ->get();

        return view('admin.schools.grade_levels.class_edit', compact('school', 'section', 'class', 'academicYears', 'teachers'));
    }

    public function updateClass(Request $request, School $school, Section $section, ClassRoom $class)
    {
        abort_unless(
            $section->school_id === $school->id && $class->section_id === $section->id,
            404
        );

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|integer|min:1|max:12',
            'gender' => 'required|in:boys,girls,mixed',
            // رقم الفصل — unique within this grade, excluding this class (Rule #1).
            'number' => [
                'nullable', 'integer', 'min:1', 'max:100',
                Rule::unique('classes', 'number')
                    ->where(fn ($q) => $q
                        ->where('section_id', $section->id)
                        ->where('academic_year_id', $request->input('academic_year_id')))
                    ->ignore($class->id),
            ],
            'lead_teacher_id' => 'nullable|exists:users,id',
            'capacity' => 'required|integer|min:1|max:200',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        // Capacity cannot drop below the number of students already enrolled.
        $enrolled = $class->students()->count();
        if ($validated['capacity'] < $enrolled) {
            return back()
                ->withInput()
                ->with('error', __('schools.capacity_below_students', ['count' => $enrolled]));
        }

        $class->update($validated);

        return redirect()
            ->route('admin.schools.grade-levels.classes', [$school, $section])
            ->with('success', __('common.updated_successfully'));
    }

    public function showStudents(School $school, Section $section, ClassRoom $class)
    {
        abort_unless(
            $section->school_id === $school->id && $class->section_id === $section->id,
            404
        );

        $students = $class->students()->with('roles')->paginate(20);
        $otherClasses = ClassRoom::whereHas('section', fn($q) => $q->where('school_id', $school->id))
            ->where('id', '!=', $class->id)
            ->with('section')
            ->get();
        $availableStudents = User::where('school_id', $school->id)
            ->whereHas('roles', fn($r) => $r->where('slug', 'student'))
            ->whereDoesntHave('enrolledClasses')
            ->get();

        return view('admin.schools.grade_levels.students', compact('school', 'section', 'class', 'students', 'otherClasses', 'availableStudents'));
    }

    public function addStudent(Request $request, School $school, Section $section, ClassRoom $class)
    {
        abort_unless(
            $section->school_id === $school->id && $class->section_id === $section->id,
            404
        );

        $validated = $request->validate(['student_id' => 'required|exists:users,id']);

        $class->students()->syncWithoutDetaching([$validated['student_id']]);
        return back()->with('success', __('schools.student_added'));
    }

    public function transferStudents(Request $request, School $school, Section $section, ClassRoom $class)
    {
        abort_unless(
            $section->school_id === $school->id && $class->section_id === $section->id,
            404
        );

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:users,id',
            'target_class_id' => 'required|exists:classes,id|different:'.$class->id,
        ]);

        DB::transaction(function () use ($class, $validated) {
            $class->students()->detach($validated['student_ids']);
            ClassRoom::find($validated['target_class_id'])
                ->students()
                ->syncWithoutDetaching($validated['student_ids']);
        });

        return back()->with('success', __('schools.students_transferred'));
    }
}
