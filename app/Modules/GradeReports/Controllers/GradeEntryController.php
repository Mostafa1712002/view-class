<?php

namespace App\Modules\GradeReports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\GradeReport;
use App\Models\StudentGradeValue;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeEntryController extends Controller
{
    private function schoolId(): ?int
    {
        $u = auth()->user();
        return $u && method_exists($u, 'isSuperAdmin') && $u->isSuperAdmin() ? null : $u?->school_id;
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();

        $reports = GradeReport::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();

        $classes = ClassRoom::query()
            ->when($schoolId, fn($q) => $q->whereHas('section', fn($q2) => $q2->where('school_id', $schoolId)))
            ->orderBy('grade_level')->orderBy('name')->get();

        $subjects = Subject::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get();

        $selected = [
            'report_id' => $request->integer('report_id') ?: null,
            'class_id' => $request->integer('class_id') ?: null,
            'subject_id' => $request->integer('subject_id') ?: null,
        ];

        $report = null;
        $columns = collect();
        $students = collect();
        $values = collect();

        if ($selected['report_id']) {
            $report = $reports->firstWhere('id', $selected['report_id']);
            if ($report) {
                $report->load('columns');
                $columns = $report->columns->where('is_visible', true)->values();

                if (!$selected['class_id'] && $report->class_id) {
                    $selected['class_id'] = $report->class_id;
                }
                if (!$selected['subject_id'] && $report->subject_id) {
                    $selected['subject_id'] = $report->subject_id;
                }

                if ($selected['class_id']) {
                    $class = ClassRoom::with(['students' => function ($q) {
                        $q->orderBy('name');
                    }])->find($selected['class_id']);
                    if ($class) {
                        $students = $class->students;
                    }
                }

                if ($students->isNotEmpty()) {
                    $values = StudentGradeValue::query()
                        ->where('grade_report_id', $report->id)
                        ->whereIn('student_id', $students->pluck('id'))
                        ->get()
                        ->keyBy(fn($v) => $v->student_id . '-' . $v->grade_report_column_id);
                }
            }
        }

        return view('admin.grades.index-dynamic', compact(
            'reports', 'classes', 'subjects', 'selected', 'report', 'columns', 'students', 'values'
        ));
    }

    public function store(Request $request)
    {
        $reportId = (int) $request->input('report_id');
        $report = GradeReport::query()
            ->when($this->schoolId(), fn($q, $sid) => $q->where('school_id', $sid))
            ->find($reportId);
        abort_unless($report, 404);

        if ($report->is_locked) {
            return back()->withErrors(['report' => trans('grades_admin.report_locked')]);
        }

        $rows = $request->input('rows', []); // rows[student_id][column_id] = score
        $userId = auth()->id();

        DB::transaction(function () use ($rows, $report, $userId) {
            foreach ($rows as $studentId => $cols) {
                if (!is_array($cols)) continue;
                foreach ($cols as $columnId => $score) {
                    $score = $score === '' ? null : $score;
                    if ($score === null) {
                        // remove existing value if user cleared the cell
                        StudentGradeValue::where([
                            ['grade_report_id', $report->id],
                            ['grade_report_column_id', (int) $columnId],
                            ['student_id', (int) $studentId],
                        ])->delete();
                        continue;
                    }
                    StudentGradeValue::updateOrCreate(
                        [
                            'grade_report_column_id' => (int) $columnId,
                            'student_id' => (int) $studentId,
                        ],
                        [
                            'grade_report_id' => $report->id,
                            'score' => (float) $score,
                            'recorded_by' => $userId,
                        ]
                    );
                }
            }
        });

        return back()
            ->with('success', trans('grades_admin.saved_ok'))
            ->withInput($request->only(['report_id', 'class_id', 'subject_id']));
    }
}
