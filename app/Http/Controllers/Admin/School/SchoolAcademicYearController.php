<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\StudyWeek;
use App\Services\AcademicYearMigrationService;
use Illuminate\Http\Request;

class SchoolAcademicYearController extends Controller
{
    public function index(School $school)
    {
        $years = $school->academicYears()
            ->with(['terms.weeks'])
            ->orderByDesc('start_date')
            ->get();

        $current = $years->firstWhere('is_current', true);

        return view('admin.schools.academic_years.index', compact('school', 'years', 'current'));
    }

    public function storeYear(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'sometimes|boolean',
        ]);
        $validated['school_id'] = $school->id;

        $year = AcademicYear::create($validated);

        if ($request->boolean('is_current')) {
            $year->setAsCurrent();
        }

        return back()->with('success', __('schools.year_created'));
    }

    public function storeTerm(Request $request, School $school, AcademicYear $year)
    {
        abort_unless($year->school_id === $school->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $validated['academic_year_id'] = $year->id;
        $validated['sort_order'] = ($year->terms()->max('sort_order') ?? 0) + 1;

        AcademicTerm::create($validated);

        return back()->with('success', __('schools.term_created'));
    }

    public function setCurrentTerm(School $school, AcademicYear $year, AcademicTerm $term)
    {
        abort_unless($year->school_id === $school->id && $term->academic_year_id === $year->id, 404);

        AcademicTerm::where('academic_year_id', $year->id)
            ->where('id', '!=', $term->id)
            ->update(['is_current' => false]);
        $term->update(['is_current' => true]);

        return back()->with('success', __('schools.term_set_current'));
    }

    public function destroyTerm(School $school, AcademicYear $year, AcademicTerm $term)
    {
        abort_unless($year->school_id === $school->id && $term->academic_year_id === $year->id, 404);
        $term->delete();
        return back()->with('success', __('common.deleted_successfully'));
    }

    public function storeWeek(Request $request, School $school, AcademicYear $year, AcademicTerm $term)
    {
        abort_unless($year->school_id === $school->id && $term->academic_year_id === $year->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $validated['academic_term_id'] = $term->id;
        $validated['sort_order'] = ($term->weeks()->max('sort_order') ?? 0) + 1;

        StudyWeek::create($validated);

        return back()->with('success', __('schools.week_created'));
    }

    public function destroyWeek(School $school, AcademicYear $year, AcademicTerm $term, StudyWeek $week)
    {
        abort_unless(
            $year->school_id === $school->id
            && $term->academic_year_id === $year->id
            && $week->academic_term_id === $term->id,
            404
        );
        $week->delete();
        return back()->with('success', __('common.deleted_successfully'));
    }

    public function promote(School $school, AcademicYear $year)
    {
        abort_unless($year->school_id === $school->id, 404);

        // The old one-click promote is replaced by the explicit migration page
        // (choose what to migrate + source/destination). Keep the route working.
        return redirect()->route('admin.schools.academic-years.migrate', $school);
    }

    /**
     * Year-rollover migration page: pick a type (classes / students) and the
     * source + destination. Time-slots are school-global (carry over
     * automatically); lessons rollover is handled by the lessons module.
     */
    public function migrate(School $school)
    {
        $years = $school->academicYears()->orderByDesc('start_date')->get();
        $sections = $school->sections()->orderBy('level')->orderBy('name')->get();
        $classes = ClassRoom::whereHas('section', fn ($q) => $q->where('school_id', $school->id))
            ->withCount('students')
            ->get(['id', 'name', 'section_id', 'academic_year_id']);

        return view('admin.schools.academic_years.migrate', compact('school', 'years', 'sections', 'classes'));
    }

    public function migrateClasses(Request $request, School $school, AcademicYearMigrationService $service)
    {
        $validated = $request->validate([
            'source_year_id' => 'required|integer',
            'destination_year_id' => 'required|integer|different:source_year_id',
        ]);

        $source = $school->academicYears()->findOrFail($validated['source_year_id']);
        $destination = $school->academicYears()->findOrFail($validated['destination_year_id']);

        $result = $service->migrateClasses($school, $source, $destination);

        return redirect()
            ->route('admin.schools.academic-years.migrate', $school)
            ->with('success', __('schools.migrate_classes_result', $result));
    }

    public function migrateStudents(Request $request, School $school, AcademicYearMigrationService $service)
    {
        $validated = $request->validate([
            'source_class_id' => 'required|integer',
            'destination_class_id' => 'required|integer|different:source_class_id',
        ]);

        $scoped = fn ($id) => ClassRoom::whereHas('section', fn ($q) => $q->where('school_id', $school->id))
            ->findOrFail($id);

        $source = $scoped($validated['source_class_id']);
        $destination = $scoped($validated['destination_class_id']);

        $result = $service->promoteStudents($source, $destination);

        return redirect()
            ->route('admin.schools.academic-years.migrate', $school)
            ->with('success', __('schools.migrate_students_result', $result));
    }
}
