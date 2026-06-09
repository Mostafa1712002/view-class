<?php

namespace App\Modules\GradeReports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\GradeReport;
use App\Models\StudentGradeValue;
use App\Modules\GradeReports\Repositories\Contracts\GradeReportRepository;
use Illuminate\Http\Request;

class GradeReportPrintController extends Controller
{
    public function __construct(private GradeReportRepository $reports) {}

    private function schoolId(): ?int
    {
        $u = auth()->user();
        return $u && method_exists($u, 'isSuperAdmin') && $u->isSuperAdmin() ? null : $u?->school_id;
    }

    /**
     * Transcript (كشف الدرجات) — all students in the report's class with their grades per column.
     */
    public function transcript(Request $request, int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);

        $columns = $report->columns->where('is_visible', true)->values();

        $classId = $request->integer('class_id') ?: $report->class_id;
        $students = collect();
        if ($classId) {
            // Scope the class to the current school (cross-tenant IDOR guard); super-admin (null) sees all.
            $class = ClassRoom::with(['students' => fn($q) => $q->orderBy('name')])
                ->when($this->schoolId(), fn($q) => $q->whereHas('section', fn($q2) => $q2->where('school_id', $this->schoolId())))
                ->find($classId);
            if ($class) {
                $students = $class->students;
            }
        }

        $values = collect();
        if ($students->isNotEmpty()) {
            $values = StudentGradeValue::where('grade_report_id', $report->id)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy(fn($v) => $v->student_id . '-' . $v->grade_report_column_id);
        }

        $classes = ClassRoom::query()
            ->when($this->schoolId(), fn($q) => $q->whereHas('section', fn($q2) => $q2->where('school_id', $this->schoolId())))
            ->orderBy('grade_level')->orderBy('name')->get();

        return view('admin.grade-reports.transcript', compact('report', 'columns', 'students', 'values', 'classes', 'classId'));
    }

    /**
     * Notification (إشعار الدرجات) — per-student grade notification document.
     * Respects visible_to_student / visible_to_parent and opens_at/closes_at.
     */
    public function notification(Request $request, int $id)
    {
        $report = $this->reports->findScoped($id, $this->schoolId());
        abort_unless($report, 404);

        $columns = $report->columns->where('is_visible', true)->values();

        $classId = $request->integer('class_id') ?: $report->class_id;
        $students = collect();
        if ($classId) {
            // Scope the class to the current school (cross-tenant IDOR guard); super-admin (null) sees all.
            $class = ClassRoom::with(['students' => fn($q) => $q->orderBy('name')])
                ->when($this->schoolId(), fn($q) => $q->whereHas('section', fn($q2) => $q2->where('school_id', $this->schoolId())))
                ->find($classId);
            if ($class) {
                $students = $class->students;
            }
        }

        $selectedStudentId = $request->integer('student_id') ?: null;
        $student = null;
        $studentValues = collect();

        if ($selectedStudentId) {
            // Derive from the already school-scoped class members so the student is guaranteed
            // to belong to this school AND this report's class (cross-tenant IDOR guard).
            $student = $students->firstWhere('id', $selectedStudentId);
            if ($student) {
                $studentValues = StudentGradeValue::where('grade_report_id', $report->id)
                    ->where('student_id', $student->id)
                    ->get()
                    ->keyBy('grade_report_column_id');
            }
        }

        $classes = ClassRoom::query()
            ->when($this->schoolId(), fn($q) => $q->whereHas('section', fn($q2) => $q2->where('school_id', $this->schoolId())))
            ->orderBy('grade_level')->orderBy('name')->get();

        $isPublished = $report->opens_at && $report->opens_at->isPast()
            && (!$report->closes_at || $report->closes_at->isFuture());

        return view('admin.grade-reports.notification', compact(
            'report', 'columns', 'students', 'studentValues', 'student',
            'classes', 'classId', 'selectedStudentId', 'isPublished'
        ));
    }
}
