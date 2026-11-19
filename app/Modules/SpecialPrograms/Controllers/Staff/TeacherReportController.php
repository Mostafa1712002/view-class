<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Controllers\Concerns\ExportsReports;
use App\Modules\SpecialPrograms\DTOs\ReportFilters;
use App\Modules\SpecialPrograms\Models\SpExam;
use App\Modules\SpecialPrograms\Repositories\Contracts\ExamRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ReportRepository;
use App\Modules\SpecialPrograms\Support\Programs;
use Illuminate\Http\Request;

/** «تقاريري»: a program teacher's own exams and results, limited to the programs/subjects he is assigned. */
class TeacherReportController extends Controller
{
    use ExportsReports;

    public function __construct(private ReportRepository $reports, private ExamRepository $exams, private GroupRepository $groups) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $programs = array_values(array_filter(Programs::keys(),
            fn ($p) => $this->groups->teacherSubjectIds($schoolId, $user->id, $p) !== []));
        $program = $request->query('program');
        $selected = in_array($program, $programs, true) ? [$program] : $programs;

        $rows = collect();
        foreach ($selected as $p) {
            $rows = $rows->merge($this->reports->teacherExamSummary($user->id, new ReportFilters(school: $schoolId, program: $p)));
        }
        $rows = $rows->sortByDesc('created_at')->values();
        $active = SpExam::whereIn('id', $rows->pluck('exam_id'))->where('expires_at', '>', now())->count();
        $stats = [
            'exams' => $rows->count(), 'students' => $rows->sum('targeted'), 'completed' => $rows->sum('completed'),
            'pending' => $rows->sum('pending'), 'active' => $active,
            'avg' => round((float) $rows->where('avg_pct', '>', 0)->avg('avg_pct'), 2),
        ];

        return view('special-programs.staff.teacher.index', compact('rows', 'stats', 'programs') + ['program' => $program]);
    }

    public function exam(Request $request, int $id)
    {
        [$exam, $report] = $this->load($request, $id);

        return view('special-programs.staff.teacher.exam', compact('exam', 'report'));
    }

    public function export(Request $request, int $id)
    {
        [$exam, $report] = $this->load($request, $id);
        $rows = $this->reports->studentResults($this->filters($request, $exam))->groupBy('student_id')->map(function ($s) {
            $pct = $s->sum('items_count') > 0 ? round($s->sum('correct_count') * 100 / $s->sum('items_count'), 2) : 0;

            return [$s->first()->student_name, $s->first()->class_name, $pct, $s->first()->level['name'] ?? ''];
        })->values();

        return $this->exportExcel([
            __('special_programs/teacher.student'), __('special_programs/teacher.class'), __('special_programs/teacher.pct'), __('special_programs/teacher.level'),
        ], $rows, 'exam-'.$exam->id.'-teacher-report.xlsx');
    }

    private function filters(Request $request, SpExam $exam): ReportFilters
    {
        $user = $request->user();
        $allowed = $this->groups->teacherSubjectIds((int) $user->school_id, $user->id, $exam->program);

        return new ReportFilters(school: (int) $user->school_id, program: $exam->program, exam: $exam->id, teacherId: $user->id,
            subject: $allowed !== null && count($allowed) === 1 ? $allowed[0] : null);
    }

    /** @return array{0: SpExam, 1: array} */
    private function load(Request $request, int $id): array
    {
        $user = $request->user();
        $exam = $this->exams->findForUser($id, $user);          // 404 outside his scope
        $allowed = $this->groups->teacherSubjectIds((int) $user->school_id, $user->id, $exam->program);
        abort_if($allowed === [], 403);                            // not assigned to this program
        $report = $this->reports->examReport($exam->id, $this->filters($request, $exam));
        if ($allowed !== null && count($allowed) > 1) {            // several (not all) subjects: hide the others' rows
            $report['subjects'] = $report['subjects']->whereIn('subject_id', $allowed)->values();
        }

        return [$exam, $report];
    }
}
