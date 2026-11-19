<?php

namespace App\Modules\SpecialPrograms\Repositories;

use App\Modules\SpecialPrograms\DTOs\ReportFilters;
use App\Modules\SpecialPrograms\Repositories\Contracts\ReportRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\PerformanceLevels;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Report numbers from sp_attempts / sp_attempt_items snapshots only (never bank_questions).
 * Only closed attempts of non-deleted exams. School scope = $f->school (null = all schools);
 * teacher scope = $f->teacherId (exams he created / owns, or exam subjects he teaches).
 * Student class = users.class_room_id (class_student pivot is honoured for the class/grade filters).
 */
final class EloquentReportRepository implements ReportRepository
{
    private const CLOSED = ['completed', 'timed_out', 'auto_ended'];

    /** Distribution bands per program (D§9): [min, max, label]. */
    private const DISTRIBUTION = [
        'tahsili' => [[95, 100, '95-100'], [90, 94, '90-94'], [85, 89, '85-89'], [80, 84, '80-84'], [70, 79, '70-79'], [60, 69, '60-69'], [0, 59, '<60']],
        'nafis' => [[90, 100, '90-100'], [80, 89, '80-89'], [70, 79, '70-79'], [60, 69, '60-69'], [50, 59, '50-59'], [0, 49, '<50']],
        'qiyas' => [[90, 100, '90-100'], [80, 89, '80-89'], [70, 79, '70-79'], [60, 69, '60-69'], [50, 59, '50-59'], [0, 49, '<50']],
    ];

    private array $bandCache = [];

    public function __construct(private SettingsRepository $settings) {}

    // ---------------------------------------------------------------- base queries

    private function attempts(ReportFilters $f): Builder
    {
        $q = DB::table('sp_attempts as a')
            ->join('sp_exams as e', 'e.id', '=', 'a.exam_id')
            ->join('sp_exam_subjects as es', 'es.id', '=', 'a.exam_subject_id')
            ->whereNull('e.deleted_at')
            ->whereIn('a.status', self::CLOSED)
            ->when($f->school !== null, fn ($q) => $q->where('a.school_id', $f->school))
            ->when($f->program, fn ($q) => $q->where('a.program', $f->program))
            ->when($f->exam, fn ($q) => $q->where('a.exam_id', $f->exam))
            ->when($f->subject, fn ($q) => $q->where('es.sp_subject_id', $f->subject))
            ->when($f->origin, fn ($q) => $q->where('e.creator_role', $f->origin))
            ->when($f->from, fn ($q) => $q->where('a.ended_at', '>=', $f->from.' 00:00:00'))
            ->when($f->to, fn ($q) => $q->where('a.ended_at', '<=', $f->to.' 23:59:59'));
        $this->teacherScope($q, $f->teacherId);
        if ($f->grade || $f->class) {
            $q->whereIn('a.student_id', $this->studentIdsOfClasses($f));
        }
        if ($f->group) {
            $q->whereIn('a.student_id', DB::table('sp_enrollments')->whereNull('deleted_at')
                ->where('group_id', $f->group)->select('student_id'));
        }

        return $q;
    }

    private function teacherScope(Builder $q, ?int $teacherId): void
    {
        if ($teacherId === null) {
            return;
        }
        $q->where(fn ($w) => $w->where('e.teacher_id', $teacherId)->orWhere('e.created_by', $teacherId)
            ->orWhereExists(fn ($s) => $s->from('sp_exam_subjects as ts')->whereColumn('ts.exam_id', 'e.id')
                ->where('ts.teacher_id', $teacherId)->selectRaw('1')));
    }

    private function studentIdsOfClasses(ReportFilters $f): Builder
    {
        $classes = DB::table('classes')->select('id')
            ->when($f->grade, fn ($q) => $q->where('grade_level', $f->grade))
            ->when($f->class, fn ($q) => $q->where('id', $f->class));

        return DB::table('users')->select('id')->where(fn ($w) => $w
            ->whereIn('class_room_id', (clone $classes))
            ->orWhereIn('id', DB::table('class_student')->select('student_id')->whereIn('class_id', (clone $classes))));
    }

    private function level(?int $school, string $program, float $pct): array
    {
        $bands = $this->bandCache[$school.'|'.$program] ??= $this->settings->bandsFor($school, $program);

        return PerformanceLevels::for($pct, $bands);
    }

    private function with(ReportFilters $f, array $override): ReportFilters
    {
        return new ReportFilters(...array_merge(get_object_vars($f), $override));
    }

    private function pct($num, $den): float
    {
        return $den > 0 ? round($num * 100 / $den, 2) : 0.0;
    }

    // ---------------------------------------------------------------- reports

    public function studentResults(ReportFilters $f): Collection
    {
        $rows = $this->attempts($f)
            ->join('users as u', 'u.id', '=', 'a.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 'u.class_room_id')
            ->leftJoin('sp_enrollments as en', fn ($j) => $j->on('en.student_id', '=', 'a.student_id')
                ->on('en.school_id', '=', 'a.school_id')->on('en.program', '=', 'a.program')->whereNull('en.deleted_at'))
            ->leftJoin('sp_groups as g', 'g.id', '=', 'en.group_id')
            ->join('sp_subjects as s', 's.id', '=', 'es.sp_subject_id')
            ->select('a.id as attempt_id', 'a.school_id', 'a.program', 'a.student_id', 'u.name as student_name',
                'u.national_id', 'c.name as class_name', 'g.name as group_name', 's.name_ar as subject_name',
                'e.id as exam_id', 'e.title as exam_title', 'e.creator_role', 'a.correct_count', 'a.items_count',
                'a.score_pct', 'a.time_spent_seconds', 'a.ended_at')
            ->orderByDesc('a.ended_at')->orderByDesc('a.id')->get();

        return $rows->map(function ($r) {
            $r->level = $this->level($r->school_id, $r->program, (float) $r->score_pct);

            return $r;
        });
    }

    public function bySubject(ReportFilters $f): Collection
    {
        return $this->attempts($f)->join('sp_subjects as s', 's.id', '=', 'es.sp_subject_id')
            ->groupBy('es.sp_subject_id', 's.name_ar', 's.sort_order')->orderBy('s.sort_order')
            ->selectRaw('es.sp_subject_id as subject_id, s.name_ar as subject_name, count(distinct a.student_id) as students,
                count(*) as attempts, avg(a.score_pct) as avg_pct, max(a.score_pct) as max_pct, min(a.score_pct) as min_pct,
                avg(a.time_spent_seconds) as avg_time')->get();
    }

    public function bySkill(ReportFilters $f): Collection
    {
        $rows = $this->attempts($f)->join('sp_attempt_items as i', 'i.attempt_id', '=', 'a.id')
            ->join('sp_skills as k', 'k.id', '=', 'i.sp_skill_id')
            ->join('sp_subjects as s', 's.id', '=', 'k.sp_subject_id')
            ->groupBy('a.program', 'i.sp_skill_id', 'k.name', 's.name_ar', 's.sort_order', 'k.sort_order')
            ->orderBy('s.sort_order')->orderBy('k.sort_order')
            ->selectRaw('a.program, i.sp_skill_id as skill_id, k.name as skill_name, s.name_ar as subject_name,
                count(distinct a.student_id) as students, count(*) as answers,
                sum(case when i.is_correct then 1 else 0 end) as correct')->get();

        return $rows->map(function ($r) use ($f) {
            $r->success_pct = $this->pct($r->correct, $r->answers);
            // rating = the program's own configured performance levels
            $lvl = $this->level($f->school, $r->program, $r->success_pct);
            $r->rating = ['key' => $lvl['key'], 'name' => $lvl['name']];

            return $r;
        });
    }

    public function byDifficulty(ReportFilters $f): Collection
    {
        $rows = $this->attempts($f)->join('sp_attempt_items as i', 'i.attempt_id', '=', 'a.id')
            ->whereNotNull('i.difficulty')->groupBy('i.difficulty')
            ->selectRaw('i.difficulty, count(*) as answers, sum(case when i.is_correct then 1 else 0 end) as correct')
            ->get()->keyBy('difficulty');

        return collect(['easy', 'medium', 'hard'])->map(function ($d) use ($rows) {
            $r = $rows[$d] ?? (object) ['difficulty' => $d, 'answers' => 0, 'correct' => 0];
            $r->answers = (int) $r->answers;
            $r->correct = (int) $r->correct;
            $r->success_pct = $this->pct($r->correct, $r->answers);

            return $r;
        });
    }

    public function examStats(ReportFilters $f): Collection
    {
        $exams = DB::table('sp_exams as e')->whereNull('e.deleted_at')
            ->when($f->school !== null, fn ($q) => $q->where('e.school_id', $f->school))
            ->when($f->program, fn ($q) => $q->where('e.program', $f->program))
            ->when($f->exam, fn ($q) => $q->where('e.id', $f->exam))
            ->when($f->origin, fn ($q) => $q->where('e.creator_role', $f->origin));
        $this->teacherScope($exams, $f->teacherId);
        $exams = $exams->select('e.*')->orderByDesc('e.created_at')->get();

        $stats = $this->attempts($f)->groupBy('a.exam_id')
            ->selectRaw('a.exam_id, count(distinct a.student_id) as students, avg(a.score_pct) as avg_pct')
            ->get()->keyBy('exam_id');
        $subjects = DB::table('sp_exam_subjects as es')->join('sp_subjects as s', 's.id', '=', 'es.sp_subject_id')
            ->whereIn('es.exam_id', $exams->pluck('id'))->orderBy('es.order_index')
            ->get(['es.exam_id', 's.name_ar'])->groupBy('exam_id');

        return $exams->map(function ($e) use ($stats, $subjects) {
            $targeted = $this->audienceSize($e);
            $students = (int) ($stats[$e->id]->students ?? 0);

            return (object) [
                'exam_id' => $e->id, 'title' => $e->title, 'program' => $e->program, 'creator_role' => $e->creator_role,
                'subjects' => ($subjects[$e->id] ?? collect())->pluck('name_ar')->all(),
                'students' => $students, 'targeted' => $targeted,
                'participation_pct' => $this->pct($students, $targeted),
                'avg_pct' => round((float) ($stats[$e->id]->avg_pct ?? 0), 2), 'created_at' => $e->created_at,
            ];
        });
    }

    /** Enrolled students of the exam's school+program in a target class or group (D§6). */
    private function audienceSize(object $exam): int
    {
        $classIds = DB::table('sp_exam_targets')->where('exam_id', $exam->id)->whereNotNull('class_id')->pluck('class_id')->all();
        $groupIds = DB::table('sp_exam_targets')->where('exam_id', $exam->id)->whereNotNull('group_id')->pluck('group_id')->all();

        return DB::table('sp_enrollments as en')->join('users as u', 'u.id', '=', 'en.student_id')
            ->whereNull('en.deleted_at')->where('en.school_id', $exam->school_id)->where('en.program', $exam->program)
            ->where(fn ($w) => $w->whereIn('en.group_id', $groupIds)
                ->orWhereIn('u.class_room_id', $classIds)
                ->orWhereIn('u.id', DB::table('class_student')->select('student_id')->whereIn('class_id', $classIds)))
            ->distinct()->count('en.student_id');
    }

    public function distribution(ReportFilters $f): Collection
    {
        $rows = $this->attempts($f)->leftJoin('users as u', 'u.id', '=', 'a.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 'u.class_room_id')
            ->join('sp_subjects as s', 's.id', '=', 'es.sp_subject_id')
            ->get(['a.program', 'a.score_pct', 'u.class_room_id', 'c.name as class_name', 's.id as subject_id', 's.name_ar as subject_name']);

        return $rows->groupBy(fn ($r) => $r->program.'|'.$r->class_room_id.'|'.$r->subject_id)->map(function ($g) {
            $first = $g->first();
            $bands = [];
            foreach (self::DISTRIBUTION[$first->program] ?? self::DISTRIBUTION['qiyas'] as [$min, $max, $label]) {
                $bands[$label] = $g->filter(fn ($r) => $r->score_pct >= $min && round($r->score_pct, 2) < $max + 1)->count();
            }

            return (object) ['program' => $first->program, 'class_name' => $first->class_name, 'subject_name' => $first->subject_name,
                'total' => $g->count(), 'bands' => $bands];
        })->values();
    }

    public function byClass(ReportFilters $f): Collection
    {
        return $this->attempts($f)->leftJoin('users as u', 'u.id', '=', 'a.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 'u.class_room_id')
            ->join('sp_subjects as s', 's.id', '=', 'es.sp_subject_id')
            ->groupBy('u.class_room_id', 'c.name', 'es.sp_subject_id', 's.name_ar')->orderBy('c.name')->orderBy('s.name_ar')
            ->selectRaw('c.name as class_name, s.name_ar as subject_name, count(distinct a.student_id) as students,
                avg(a.score_pct) as avg_pct, sum(case when a.score_pct >= 80 then 1 else 0 end) as top_count,
                sum(case when a.score_pct < 60 then 1 else 0 end) as weak_count, avg(a.time_spent_seconds) as avg_time')->get();
    }

    public function studentReport(int $studentId, string $program): array
    {
        $student = DB::table('users as u')->leftJoin('classes as c', 'c.id', '=', 'u.class_room_id')
            ->where('u.id', $studentId)->first(['u.id', 'u.name', 'u.school_id', 'c.name as class_name']);
        $group = DB::table('sp_enrollments as en')->leftJoin('sp_groups as g', 'g.id', '=', 'en.group_id')
            ->whereNull('en.deleted_at')->where('en.student_id', $studentId)->where('en.program', $program)->value('g.name');
        $schoolId = $student->school_id ?? null;

        $attempts = DB::table('sp_attempts as a')->join('sp_exams as e', 'e.id', '=', 'a.exam_id')
            ->join('sp_exam_subjects as es', 'es.id', '=', 'a.exam_subject_id')
            ->join('sp_subjects as s', 's.id', '=', 'es.sp_subject_id')
            ->whereNull('e.deleted_at')->whereIn('a.status', self::CLOSED)
            ->where('a.student_id', $studentId)->where('a.program', $program)
            ->orderBy('a.ended_at')->orderBy('a.id')
            ->get(['a.id as attempt_id', 'a.exam_id', 'e.title as exam_title', 'es.sp_subject_id as subject_id', 's.name_ar as subject_name',
                'a.correct_count', 'a.items_count', 'a.score_pct', 'a.time_spent_seconds', 'a.ended_at'])
            ->map(function ($r) use ($schoolId, $program) {
                $r->level = $this->level($schoolId, $program, (float) $r->score_pct);

                return $r;
            });

        // ponytail: current level = mean % of attempts ended in the last 30 days, else the overall mean.
        $recent = $attempts->filter(fn ($r) => $r->ended_at >= now()->subDays(30)->toDateTimeString());
        $basis = $recent->isNotEmpty() ? $recent : $attempts;
        $mean = $basis->isNotEmpty() ? round((float) $basis->avg('score_pct'), 2) : null;

        $itemQ = DB::table('sp_attempt_items as i')->join('sp_attempts as a', 'a.id', '=', 'i.attempt_id')
            ->join('sp_exams as e', 'e.id', '=', 'a.exam_id')->whereNull('e.deleted_at')->whereIn('a.status', self::CLOSED)
            ->where('a.student_id', $studentId)->where('a.program', $program);
        if ($program === 'tahsili') {
            $detail = (clone $itemQ)->whereNotNull('i.difficulty')->groupBy('i.difficulty')
                ->selectRaw('i.difficulty, count(*) as answers, sum(case when i.is_correct then 1 else 0 end) as correct')->get()
                ->keyBy('difficulty');
            $detail = collect(['easy', 'medium', 'hard'])->map(function ($d) use ($detail) {
                $r = $detail[$d] ?? (object) ['difficulty' => $d, 'answers' => 0, 'correct' => 0];
                $r->success_pct = $this->pct($r->correct, $r->answers);

                return $r;
            });
        } else {
            $detail = (clone $itemQ)->join('sp_skills as k', 'k.id', '=', 'i.sp_skill_id')->groupBy('i.sp_skill_id', 'k.name')
                ->orderBy('k.name')
                ->selectRaw('i.sp_skill_id as skill_id, k.name as skill_name, count(*) as answers, sum(case when i.is_correct then 1 else 0 end) as correct')
                ->get()->map(function ($r) {
                    $r->success_pct = $this->pct($r->correct, $r->answers);

                    return $r;
                });
        }

        return [
            'header' => [
                'student_id' => $studentId, 'name' => $student->name ?? null, 'class_name' => $student->class_name ?? null,
                'group_name' => $group, 'program' => $program, 'mean_pct' => $mean,
                'level' => $mean === null ? null : $this->level($schoolId, $program, $mean),
            ],
            'trend' => $attempts->map(fn ($r) => ['attempt_id' => $r->attempt_id, 'ended_at' => $r->ended_at, 'pct' => (float) $r->score_pct, 'subject_name' => $r->subject_name])->values()->all(),
            'subjects' => $attempts->groupBy('subject_id')->map(fn ($g) => [
                'subject_id' => $g->first()->subject_id, 'subject_name' => $g->first()->subject_name, 'attempts' => $g->count(),
                'last_pct' => (float) $g->last()->score_pct, 'best_pct' => (float) $g->max('score_pct'), 'mean_pct' => round((float) $g->avg('score_pct'), 2),
            ])->values()->all(),
            ($program === 'tahsili' ? 'difficulty' : 'skills') => $detail->values()->all(),
            'history' => $attempts->reverse()->values()->all(),
        ];
    }

    public function teacherExamSummary(int $teacherId, ReportFilters $f): Collection
    {
        return $this->examStats($this->with($f, ['teacherId' => $teacherId]))->map(function ($r) use ($teacherId, $f) {
            $g = $this->with($f, ['teacherId' => $teacherId, 'exam' => $r->exam_id]);
            $completed = $this->attempts($g)->count();
            $r->subjects_count = count($r->subjects);
            $r->total = $r->targeted * max($r->subjects_count, 1);
            $r->completed = $completed;
            $r->pending = max($r->total - $completed, 0);

            return $r;
        });
    }

    public function examReport(int $examId, ReportFilters $f): array
    {
        $g = $this->with($f, ['exam' => $examId]);
        $rows = $this->studentResults($g);

        $perStudent = $rows->groupBy('student_id')->map(fn ($s) => (object) [
            'student_id' => $s->first()->student_id, 'student_name' => $s->first()->student_name,
            'class_name' => $s->first()->class_name,
            'pct' => $this->pct($s->sum('correct_count'), $s->sum('items_count')),
            'level' => $this->level($s->first()->school_id, $s->first()->program, $this->pct($s->sum('correct_count'), $s->sum('items_count'))),
        ])->values();

        $levels = $rows->groupBy(fn ($r) => $r->level['key'])->map->count();
        $bands = $rows->isEmpty() ? [] : $this->settings->bandsFor($rows->first()->school_id, $rows->first()->program);

        $skills = $this->attempts($g)->join('sp_attempt_items as i', 'i.attempt_id', '=', 'a.id')
            ->join('sp_skills as k', 'k.id', '=', 'i.sp_skill_id')->join('users as u', 'u.id', '=', 'a.student_id')
            ->groupBy('a.student_id', 'u.name', 'i.sp_skill_id', 'k.name')->orderBy('u.name')->orderBy('k.name')
            ->selectRaw('a.student_id, u.name as student_name, i.sp_skill_id as skill_id, k.name as skill_name, count(*) as answers,
                sum(case when i.is_correct then 1 else 0 end) as correct')->get();

        return [
            'avg_pct' => round((float) $perStudent->avg('pct'), 2),
            'max_pct' => (float) ($perStudent->max('pct') ?? 0),
            'min_pct' => (float) ($perStudent->min('pct') ?? 0),
            'students' => $perStudent->count(),
            'top10' => $perStudent->sortByDesc('pct')->take(10)->values(),
            'weak10' => $perStudent->sortBy('pct')->take(10)->values(),
            'levels' => collect($bands)->map(fn ($b) => $b + ['count' => (int) ($levels[$b['key']] ?? 0)])->values()->all(),
            'subjects' => $this->bySubject($g),
            'student_skills' => $skills->groupBy('student_id')->map(fn ($s) => [
                'student_id' => $s->first()->student_id, 'student_name' => $s->first()->student_name,
                'skills' => $s->map(fn ($r) => ['skill_id' => $r->skill_id, 'skill_name' => $r->skill_name, 'answers' => (int) $r->answers, 'correct' => (int) $r->correct])->all(),
            ])->values()->all(),
        ];
    }
}
