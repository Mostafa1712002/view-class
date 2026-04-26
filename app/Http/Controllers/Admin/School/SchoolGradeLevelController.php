<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolGradeLevelController extends Controller
{
    public function index(School $school)
    {
        $sections = $school->sections()
            ->with(['classes' => fn($q) => $q->withCount('students')])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('admin.schools.grade_levels.index', compact('school', 'sections'));
    }

    public function storeSection(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'level' => 'required|in:primary,intermediate,secondary',
        ]);
        $validated['school_id'] = $school->id;

        Section::create($validated);

        return back()->with('success', __('common.created_successfully'));
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
