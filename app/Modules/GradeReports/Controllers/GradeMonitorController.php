<?php

namespace App\Modules\GradeReports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\GradeReport;
use App\Models\GradeReportColumn;
use App\Models\StudentGradeValue;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class GradeMonitorController extends Controller
{
    private function schoolId(): ?int
    {
        $u = auth()->user();
        return $u && method_exists($u, 'isSuperAdmin') && $u->isSuperAdmin() ? null : $u?->school_id;
    }

    /**
     * Grade-monitoring overview page.
     * Shows per-report × class × subject completion status.
     */
    public function index(Request $request)
    {
        $schoolId = $this->schoolId();

        // Reference data for filters
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

        // Filters from request
        $filters = [
            'year_id'    => $request->integer('year_id') ?: null,
            'term_id'    => $request->integer('term_id') ?: null,
            'class_id'   => $request->integer('class_id') ?: null,
            'subject_id' => $request->integer('subject_id') ?: null,
            'status'     => $request->get('status'), // complete|missing|all
        ];

        // Build monitoring rows
        $rows = $this->buildMonitorRows($schoolId, $filters);

        $stats = [
            'total'    => count($rows),
            'complete' => count(array_filter($rows, fn($r) => $r['status'] === 'complete')),
            'missing'  => count(array_filter($rows, fn($r) => $r['status'] === 'missing')),
            'empty'    => count(array_filter($rows, fn($r) => $r['status'] === 'empty')),
        ];

        return view('admin.grade-reports.monitor', compact('rows', 'stats', 'years', 'terms', 'classes', 'subjects', 'filters'));
    }

    /**
     * Export monitoring data as CSV.
     */
    public function export(Request $request)
    {
        $schoolId = $this->schoolId();
        $filters = [
            'year_id'    => $request->integer('year_id') ?: null,
            'term_id'    => $request->integer('term_id') ?: null,
            'class_id'   => $request->integer('class_id') ?: null,
            'subject_id' => $request->integer('subject_id') ?: null,
            'status'     => $request->get('status'),
        ];

        $rows = $this->buildMonitorRows($schoolId, $filters);

        $headers = ['التقرير', 'الصف', 'المادة', 'المعلم', 'الطلاب المسجلون', 'درجات مُدخلة', 'درجات ناقصة', 'الحالة'];
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $output .= implode(',', array_map(fn($h) => '"' . $h . '"', $headers)) . "\n";

        foreach ($rows as $row) {
            $statusLabel = match ($row['status']) {
                'complete' => 'مكتمل',
                'missing'  => 'ناقص',
                default    => 'لا توجد درجات',
            };
            $cells = [
                $row['report_title'],
                $row['class_name'],
                $row['subject_name'],
                $row['teacher_name'],
                $row['students_count'],
                $row['entered_count'],
                $row['missing_count'],
                $statusLabel,
            ];
            $output .= implode(',', array_map(fn($c) => '"' . str_replace('"', '""', (string)$c) . '"', $cells)) . "\n";
        }

        return Response::make($output, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="grade-monitor-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Build the monitoring rows array.
     * Each row = one report + class combination.
     * expected = enrolled_students × visible_columns
     * entered  = distinct (student, column) cells with a value in student_grade_values
     */
    private function buildMonitorRows(?int $schoolId, array $filters): array
    {
        // Query reports with their columns and class relationship
        $reportsQuery = GradeReport::with(['classRoom', 'subject', 'columns' => fn($q) => $q->where('is_visible', true)])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($filters['year_id'], fn($q) => $q->where('academic_year_id', $filters['year_id']))
            ->when($filters['term_id'], fn($q) => $q->where('academic_term_id', $filters['term_id']))
            ->when($filters['class_id'], fn($q) => $q->where('class_id', $filters['class_id']))
            ->when($filters['subject_id'], fn($q) => $q->where('subject_id', $filters['subject_id']))
            ->latest();

        $reports = $reportsQuery->get();

        // Preload teacher assignments: subject_teacher[subject_id] → teachers
        $teachersBySubject = DB::table('subject_teacher')
            ->join('users', 'users.id', '=', 'subject_teacher.user_id')
            ->select('subject_teacher.subject_id', 'users.name as teacher_name')
            ->get()
            ->groupBy('subject_id');

        $rows = [];

        foreach ($reports as $report) {
            $columns   = $report->columns; // already filtered to is_visible=true
            $colCount  = $columns->count();
            $columnIds = $columns->pluck('id')->toArray();

            // Determine the class(es) to check — if report has a class_id, use that; otherwise skip (no enrollment target)
            $classId = $report->class_id;
            if (!$classId) {
                // Report has no class scope — skip enrollment-based monitoring
                $studentsCount = 0;
                $expectedCount = 0;
                $enteredCount  = 0;
                $missingCount  = 0;
                $className     = '—';
                $teacherName   = '—';
                $subjectName   = $report->subject?->name ?? '—';
            } else {
                // Count enrolled students in class_student pivot
                $studentsCount = DB::table('class_student')->where('class_id', $classId)->count();

                $expectedCount = $studentsCount * $colCount;

                $enteredCount = $columnIds
                    ? StudentGradeValue::where('grade_report_id', $report->id)
                        ->whereIn('grade_report_column_id', $columnIds)
                        ->whereNotNull('score')
                        ->count()
                    : 0;

                $missingCount = max(0, $expectedCount - $enteredCount);
                $className    = $report->classRoom?->name ?? '—';
                $subjectName  = $report->subject?->name ?? '—';

                // Teacher name: look up via subject_id
                $teacherName = '—';
                if ($report->subject_id && isset($teachersBySubject[$report->subject_id])) {
                    $names = $teachersBySubject[$report->subject_id]->pluck('teacher_name')->unique()->implode(', ');
                    $teacherName = $names ?: '—';
                }
            }

            $status = 'empty';
            if ($expectedCount > 0) {
                $status = ($missingCount === 0) ? 'complete' : 'missing';
            }

            // Apply status filter
            if ($filters['status'] && $filters['status'] !== 'all' && $status !== $filters['status']) {
                continue;
            }

            $rows[] = [
                'report_id'      => $report->id,
                'report_title'   => $report->title,
                'report_type'    => $report->type,
                'class_id'       => $classId,
                'class_name'     => $className,
                'subject_name'   => $subjectName,
                'teacher_name'   => $teacherName,
                'students_count' => $studentsCount,
                'columns_count'  => $colCount,
                'expected_count' => $expectedCount,
                'entered_count'  => $enteredCount,
                'missing_count'  => $missingCount,
                'status'         => $status,
                'is_locked'      => $report->is_locked,
                'is_active'      => $report->is_active,
            ];
        }

        return $rows;
    }
}
