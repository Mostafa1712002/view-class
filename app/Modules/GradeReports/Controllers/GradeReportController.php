<?php

namespace App\Modules\GradeReports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Modules\GradeReports\Repositories\Contracts\GradeReportRepository;
use Illuminate\Http\Request;

class GradeReportController extends Controller
{
    public function __construct(private GradeReportRepository $reports) {}

    private function schoolId(): ?int
    {
        $u = auth()->user();
        return $u && method_exists($u, 'isSuperAdmin') && $u->isSuperAdmin() ? null : $u?->school_id;
    }

    private function refDataForForm(): array
    {
        $schoolId = $this->schoolId();
        $years = AcademicYear::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('start_date')->get();
        $terms = AcademicTerm::query()
            ->when($schoolId, fn($q) => $q->whereHas('academicYear', fn($q2) => $q2->where('school_id', $schoolId)))
            ->orderByDesc('start_date')->get();
        $classes = ClassRoom::query()
            ->when($schoolId, fn($q) => $q->whereHas('section', fn($q2) => $q2->where('school_id', $schoolId)))
            ->orderBy('grade_level')->orderBy('name')->get();
        $subjects = Subject::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get();

        return compact('years', 'terms', 'classes', 'subjects');
    }

    public function index(Request $request)
    {
        $reports = $this->reports->paginate($this->schoolId(), $request->get('type'));
        return view('admin.grade-reports.index', compact('reports'));
    }

    public function create()
    {
        return view('admin.grade-reports.create-dynamic', $this->refDataForForm());
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        // For super-admins (no scoped school) prefer the explicitly picked class's school,
        // else the user's own school, else the first school in the system.
        $schoolId = $this->schoolId() ?? auth()->user()->school_id;
        if (!$schoolId && !empty($data['class_id'])) {
            $class = ClassRoom::find($data['class_id']);
            $schoolId = $class?->section?->school_id;
        }
        if (!$schoolId) {
            $schoolId = \App\Models\School::query()->orderBy('id')->value('id');
        }
        $report = $this->reports->createDynamic($data, $schoolId, auth()->id());
        return redirect()->route('admin.grade-reports.edit', $report)->with('success', trans('grades_admin.created_ok'));
    }

    public function show(int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);
        return view('admin.grade-reports.show', compact('report'));
    }

    public function edit(int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);
        return view('admin.grade-reports.edit', array_merge(
            $this->refDataForForm(),
            ['report' => $report]
        ));
    }

    public function update(Request $request, int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);

        $data = $this->validatePayload($request);
        // type is editable for an existing report
        $data['type'] = $request->validate(['type' => ['nullable', 'in:dynamic,static,gradesheet']])['type'] ?? $report->type;
        $data['is_active'] = (bool) $request->boolean('is_active', true);
        $data['is_locked'] = (bool) $request->boolean('is_locked', false);

        $this->reports->update($report, $data);

        // optional: replace columns in one shot
        if ($request->has('columns')) {
            $this->reports->replaceColumns($report, $request->input('columns', []));
        }

        return redirect()->route('admin.grade-reports.edit', $report)->with('success', trans('grades_admin.updated_ok'));
    }

    public function updateColumns(Request $request, int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);

        $this->reports->replaceColumns($report, $request->input('columns', []));
        return redirect()->route('admin.grade-reports.edit', $report)->with('success', trans('grades_admin.columns_updated_ok'));
    }

    public function destroy(int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);
        $this->reports->delete($report);
        return redirect()->route('admin.grade-reports.index')->with('success', trans('grades_admin.deleted_ok'));
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'academic_year_id' => ['nullable', 'integer'],
            'academic_term_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'grade_input_starts_at' => ['nullable', 'date'],
            'grade_input_ends_at' => ['nullable', 'date'],
            'calc_starts_at' => ['nullable', 'date'],
            'calc_ends_at' => ['nullable', 'date'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'include_behavior' => ['nullable', 'boolean'],
            'show_subject_bilingual' => ['nullable', 'boolean'],
            'visible_to_student' => ['nullable', 'boolean'],
            'visible_to_parent' => ['nullable', 'boolean'],
            'visible_to_teacher' => ['nullable', 'boolean'],
        ]);
    }
}
