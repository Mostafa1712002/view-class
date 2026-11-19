<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Attendance\Controllers\Concerns\ExportsReports;
use App\Modules\SpecialPrograms\DTOs\ReportFilters;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ReportRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\Programs;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ExportsReports, HasSchoolScope;

    private const SECTIONS = ['students', 'subjects', 'skills', 'difficulty', 'exams', 'distribution', 'classes'];

    public function __construct(
        private ReportRepository $reports,
        private SettingsRepository $settings,
        private GroupRepository $groups,
    ) {}

    // ---------------------------------------------------------------- scope

    private function program(string $program): string
    {
        abort_unless(in_array($program, Programs::keys(), true), 404);

        return $program;
    }

    /** Teachers (who are not admins) only see their own exams; school/program are always server-decided. */
    private function filters(Request $request, string $program): ReportFilters
    {
        $user = $request->user();
        $teacherOnly = ! $user->isSuperAdmin() && ! $user->isSchoolAdmin();

        return ReportFilters::fromRequest($request, [
            'school' => $this->scopedSchoolId(),
            'program' => $program,
            'teacherId' => $teacherOnly ? $user->id : null,
        ]);
    }

    private function fmtPct($v): string
    {
        return number_format((float) $v, 1).'%';
    }

    private function fmtTime($seconds): string
    {
        return $seconds === null ? '' : sprintf('%d:%02d', intdiv((int) $seconds, 60), (int) $seconds % 60);
    }

    // ---------------------------------------------------------------- sections

    /** @return array<string, array{title:string, headers:array, rows:array, links:array}> */
    private function sections(ReportFilters $f, string $program): array
    {
        $c = fn ($k) => __('special_programs/reports.cols.'.$k);
        $title = fn ($k) => __('special_programs/reports.sections.'.$k, ['program' => Programs::label($program)]);
        $out = [];

        $links = [];
        $rows = [];
        foreach ($this->reports->studentResults($f) as $i => $r) {
            $rows[] = [$i + 1, $r->student_name, $r->national_id, $r->class_name, $r->group_name, $r->subject_name, $r->exam_title,
                $r->correct_count.'/'.$r->items_count, $this->fmtPct($r->score_pct), $r->level['name'], $this->fmtTime($r->time_spent_seconds),
                substr((string) $r->ended_at, 0, 16), __('special_programs.creator_roles.'.$r->creator_role)];
            $links[$i] = route('manage.special-programs.student-report', [$program, $r->student_id]);
        }
        $out['students'] = ['title' => $title('students'), 'rows' => $rows, 'links' => $links, 'headers' => array_map($c,
            ['no', 'student', 'national_id', 'class', 'group', 'subject', 'exam', 'score', 'pct', 'level', 'time', 'date', 'origin'])];

        $out['subjects'] = ['title' => $title('subjects'), 'links' => [], 'headers' => array_map($c, ['subject', 'students', 'attempts', 'avg_pct', 'max_pct', 'min_pct', 'avg_time']),
            'rows' => $this->reports->bySubject($f)->map(fn ($r) => [$r->subject_name, $r->students, $r->attempts, $this->fmtPct($r->avg_pct),
                $this->fmtPct($r->max_pct), $this->fmtPct($r->min_pct), $this->fmtTime(round($r->avg_time))])->all()];

        if ($program === 'tahsili') {
            $out['difficulty'] = ['title' => $title('difficulty'), 'links' => [], 'headers' => array_map($c, ['difficulty', 'answers', 'correct', 'success']),
                'rows' => $this->reports->byDifficulty($f)->map(fn ($r) => [__('special_programs.difficulty.'.$r->difficulty), $r->answers, $r->correct, $this->fmtPct($r->success_pct)])->all()];
        } else {
            $out['skills'] = ['title' => $title('skills'), 'links' => [], 'headers' => array_map($c, ['no', 'skill', 'subject', 'students', 'answers', 'correct', 'success', 'rating']),
                'rows' => $this->reports->bySkill($f)->values()->map(fn ($r, $i) => [$i + 1, $r->skill_name, $r->subject_name, $r->students,
                    $r->answers, $r->correct, $this->fmtPct($r->success_pct), $r->rating['name']])->all()];
        }

        $out['exams'] = ['title' => $title('exams'), 'links' => [], 'headers' => array_map($c, ['exam', 'origin', 'subjects', 'participants', 'targeted', 'participation', 'avg_score', 'created']),
            'rows' => $this->reports->examStats($f)->map(fn ($r) => [$r->title, __('special_programs.creator_roles.'.$r->creator_role), implode('، ', $r->subjects),
                $r->students, $r->targeted, $this->fmtPct($r->participation_pct), $this->fmtPct($r->avg_pct), substr((string) $r->created_at, 0, 10)])->all()];

        $dist = $this->reports->distribution($f);
        $bandLabels = $dist->isEmpty() ? [] : array_keys($dist->first()->bands);
        $out['distribution'] = ['title' => $title('distribution'), 'links' => [], 'headers' => array_merge(array_map($c, ['class', 'subject', 'total']), $bandLabels),
            'rows' => $dist->map(fn ($r) => array_merge([$r->class_name, $r->subject_name, $r->total], array_values($r->bands)))->all()];

        $out['classes'] = ['title' => $title('classes'), 'links' => [], 'headers' => array_map($c, ['class', 'subject', 'students', 'avg_pct', 'top', 'weak', 'avg_time']),
            'rows' => $this->reports->byClass($f)->map(fn ($r) => [$r->class_name, $r->subject_name, $r->students, $this->fmtPct($r->avg_pct),
                $r->top_count, $r->weak_count, $this->fmtTime(round($r->avg_time))])->all()];

        return $out;
    }

    // ---------------------------------------------------------------- pages

    public function index(Request $request, string $program)
    {
        $this->program($program);
        $f = $this->filters($request, $program);
        $sections = $this->sections($f, $program);
        $bands = $this->settings->bandsFor($f->school, $program);

        $counts = $this->reports->studentResults($f)->groupBy(fn ($r) => $r->level['key'])->map->count();
        $chart = ['labels' => array_column($bands, 'name'), 'data' => array_map(fn ($b) => (int) ($counts[$b['key']] ?? 0), $bands),
            'colors' => array_column($bands, 'color')];

        $classes = DB::table('classes')->when($f->school, fn ($q) => $q->where('school_id', $f->school))->orderBy('name')->get(['id', 'name', 'grade_level']);

        return view('special-programs.reports.index', [
            'program' => $program, 'filters' => $f, 'sections' => $sections, 'bands' => $bands, 'chart' => $chart,
            'allSchools' => $f->school === null,
            'grades' => $classes->pluck('grade_level')->filter()->unique()->sort()->values(),
            'classes' => $f->school ? $classes : collect(),
            'groups' => $f->school ? DB::table('sp_groups')->where('school_id', $f->school)->where('program', $program)->orderBy('name')->pluck('name', 'id') : collect(),
            'subjects' => DB::table('sp_subjects')->where('program', $program)->orderBy('sort_order')->pluck('name_ar', 'id'),
            'examOptions' => $this->reports->examStats(new ReportFilters(school: $f->school, program: $program, teacherId: $f->teacherId))->pluck('title', 'exam_id'),
        ]);
    }

    public function export(Request $request, string $program)
    {
        $this->program($program);
        $sections = $this->sections($this->filters($request, $program), $program);
        $key = (string) $request->query('section');
        abort_unless(isset($sections[$key]) && in_array($key, self::SECTIONS, true), 404);

        return $this->exportExcel($sections[$key]['headers'], $sections[$key]['rows'], "sp-{$program}-{$key}.xlsx");
    }

    public function print(Request $request, string $program)
    {
        $this->program($program);
        $f = $this->filters($request, $program);

        return $this->exportPdf('special-programs.reports.pdf', [
            'program' => $program, 'sections' => $this->sections($f, $program), 'bands' => $this->settings->bandsFor($f->school, $program),
        ], "sp-{$program}-reports.pdf", 'L');
    }

    // ---------------------------------------------------------------- student report

    /** Student of the active school; teachers only for students in their exams or the groups they lead. */
    private function studentReport(Request $request, string $program, int $studentId): array
    {
        $this->program($program);
        $schoolId = $this->scopedSchoolId();
        $student = User::where('id', $studentId)->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->first();
        abort_unless($student && $student->isStudent(), 404);

        $user = $request->user();
        if (! $user->isSuperAdmin() && ! $user->isSchoolAdmin()) {
            $own = $this->reports->studentResults(new ReportFilters(school: $schoolId, program: $program, teacherId: $user->id))->contains('student_id', $studentId)
                || DB::table('sp_enrollments')->whereNull('deleted_at')->where('student_id', $studentId)->where('program', $program)
                    ->whereIn('group_id', $this->groups->groupIdsLedBy($user->id, $program))->exists();
            abort_unless($own, 404);
        }

        return $this->reports->studentReport($studentId, $program);
    }

    public function student(Request $request, string $program, int $studentId)
    {
        $report = $this->studentReport($request, $program, $studentId);

        return view('special-programs.reports.student', ['program' => $program, 'report' => $report, 'studentId' => $studentId]);
    }

    public function studentPdf(Request $request, string $program, int $studentId)
    {
        $report = $this->studentReport($request, $program, $studentId);

        return $this->exportPdf('special-programs.partials.student-report-pdf', ['report' => $report], "sp-{$program}-student-{$studentId}.pdf");
    }
}
