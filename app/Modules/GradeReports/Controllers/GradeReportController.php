<?php

namespace App\Modules\GradeReports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
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

    public function index(Request $request)
    {
        $reports = $this->reports->paginate($this->schoolId(), $request->get('type'));
        return view('admin.grade-reports.index', compact('reports'));
    }

    public function create()
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

        return view('admin.grade-reports.create-dynamic', compact('years', 'terms', 'classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'academic_year_id' => ['nullable', 'integer'],
            'academic_term_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
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

        // Super-admins keep schoolId() === null; use their own school_id (admin user
        // is seeded with school_id=1) so reports anchor to a tenant.
        $schoolId = $this->schoolId() ?? auth()->user()->school_id;
        $report = $this->reports->createDynamic($data, $schoolId, auth()->id());
        return redirect()->route('admin.grade-reports.show', $report)->with('success', 'تم إنشاء تقرير الدرجات بنجاح');
    }

    public function show(int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);
        return view('admin.grade-reports.show', compact('report'));
    }

    public function destroy(int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);
        $this->reports->delete($report);
        return redirect()->route('admin.grade-reports.index')->with('success', 'تم حذف التقرير');
    }
}
