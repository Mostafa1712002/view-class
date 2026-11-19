<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\User;
use App\Modules\Attendance\Controllers\Concerns\ExportsReports;
use App\Modules\SpecialPrograms\Actions\Exams\CreateProgramExam;
use App\Modules\SpecialPrograms\Actions\Exams\DeleteProgramExam;
use App\Modules\SpecialPrograms\Http\Requests\StoreExamRequest;
use App\Modules\SpecialPrograms\Models\SpGroup;
use App\Modules\SpecialPrograms\Models\SpSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\AttemptRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ExamRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\QuestionPoolRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\ProgramAudience;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Controllers\Concerns\ResolvesTeacherStudents;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    use ExportsReports, HasSchoolScope, ResolvesTeacherStudents;

    public function __construct(
        private ExamRepository $exams,
        private GroupRepository $groups,
        private QuestionPoolRepository $pool,
        private AttemptRepository $attempts,
        private SettingsRepository $settings,
    ) {}

    private function teacherMode(User $u): bool
    {
        return ! $u->isSuperAdmin() && ! $u->isSchoolAdmin();
    }

    public function index(Request $request, string $program)
    {
        $schoolId = $this->scopedSchoolId();
        $role = $request->query('role');
        $q = trim((string) $request->query('q'));
        $exams = $this->exams->visibleTo($request->user())->where('program', $program)
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->when(in_array($role, ['admin', 'teacher'], true), fn ($w) => $w->where('creator_role', $role))
            ->when($q !== '', fn ($w) => $w->where('title', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%'))
            ->with('subjects.subject')->withCount('attempts')->orderByDesc('id')->paginate(20)->withQueryString();
        $creators = User::whereIn('id', $exams->pluck('created_by'))->pluck('name', 'id');

        return view('special-programs.staff.exams.index', compact('program', 'exams', 'creators', 'role', 'q'));
    }

    public function create(Request $request, string $program)
    {
        $user = $request->user();
        $schoolId = $this->scopedSchoolId();
        $teacherMode = $this->teacherMode($user);
        $needsSchool = $schoolId === null;
        $showAll = $request->boolean('all');

        $classes = collect();
        $groups = collect();
        $teachers = collect();
        $allowed = null;
        if (! $needsSchool) {
            $classes = ProgramAudience::classes($schoolId, $program, $showAll);
            $groups = $this->groups->forSchool($schoolId, $program, $teacherMode ? $user->id : null);
            if ($teacherMode) {
                $classes = $classes->whereIn('id', $this->teachingClassIds($user->id))->values();
                $allowed = $this->groups->teacherSubjectIds($schoolId, $user->id, $program);
            } else {
                $teachers = User::where('school_id', $schoolId)->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->orderBy('name')->get(['id', 'name']);
            }
        }
        $subjects = SpSubject::where('program', $program)->with('skills')->orderBy('sort_order')->get()
            ->filter(fn ($s) => $allowed === null || in_array($s->id, $allowed, true))->values();
        $bank = $subjects->mapWithKeys(fn ($s) => [$s->id => [
            'id' => $s->id, 'name' => __('special_programs.subjects.'.$s->code), 'reading' => (bool) $s->picks_passages,
            'skills' => $s->skills->map(fn ($k) => ['id' => $k->id, 'name' => $k->name])->all(),
            'stats' => $this->pool->stats($s->id, []),
        ]]);

        return view('special-programs.staff.exams.create', [
            'program' => $program, 'classes' => $classes, 'groups' => $groups, 'teachers' => $teachers, 'subjects' => $subjects,
            'bank' => $bank, 'teacherMode' => $teacherMode, 'needsSchool' => $needsSchool, 'showAll' => $showAll,
            'weeks' => $this->settings->weeksCount($schoolId), 'maxHours' => $teacherMode ? 168 : 720,
            'defaultHours' => $program === 'tahsili' ? 48 : 24,
            'blocked' => $teacherMode && $allowed === [],
        ]);
    }

    public function store(StoreExamRequest $request, string $program, CreateProgramExam $create)
    {
        $schoolId = $this->scopedSchoolId() ?? abort(422, __('special_programs/exams.pick_school'));
        $exam = $create->execute($schoolId, $program, $request->user(), $request->validated());

        return redirect()->route('manage.special-programs.exams.show', [$program, $exam->id])->with('success', __('special_programs/exams.saved'));
    }

    public function show(Request $request, string $program, int $id)
    {
        $exam = $this->exams->findForUser($id, $request->user());
        abort_unless($exam->program === $program, 404);
        $monitor = $this->attempts->monitor($exam);
        $classNames = ClassRoom::whereIn('id', $exam->targets->pluck('class_id')->filter())->pluck('name', 'id');
        $groupNames = SpGroup::whereIn('id', $exam->targets->pluck('group_id')->filter())->pluck('name', 'id');
        $creator = User::find($exam->created_by, ['id', 'name']);
        $canDelete = $request->user()->isSuperAdmin() || $request->user()->isSchoolAdmin() || (int) $exam->created_by === (int) $request->user()->id;

        return view('special-programs.staff.exams.show', compact('program', 'exam', 'monitor', 'classNames', 'groupNames', 'creator', 'canDelete'));
    }

    public function destroy(Request $request, string $program, int $id, DeleteProgramExam $delete)
    {
        $exam = $this->exams->findForUser($id, $request->user());
        abort_unless($exam->program === $program, 404);
        $delete->execute($exam, $request->user());

        return redirect()->route('manage.special-programs.exams.index', $program)->with('success', __('special_programs/exams.deleted'));
    }

    public function export(Request $request, string $program, int $id)
    {
        $exam = $this->exams->findForUser($id, $request->user());
        abort_unless($exam->program === $program, 404);
        $rows = $this->attempts->monitor($exam)->map(fn ($r) => [
            $r['student_name'], $r['subject_name'],
            $r['status'] === 'not_started' ? __('special_programs/exams.not_started') : __('special_programs.attempt_status.'.$r['status']),
            $r['score_pct'] !== null ? number_format((float) $r['score_pct'], 2) : '', $r['answered_count'] ?? '', $r['items_count'] ?? '',
        ]);

        return $this->exportExcel([
            __('special_programs/exams.col_student'), __('special_programs/exams.col_subject'), __('special_programs/exams.col_status'),
            __('special_programs/exams.col_score'), __('special_programs/exams.col_answered'), __('special_programs/exams.col_items'),
        ], $rows, 'exam-'.$exam->id.'-results.xlsx');
    }

    /** Live pool numbers for the builder: counts under the chosen term / weeks / levels / range. */
    public function bankStats(Request $request, string $program)
    {
        $data = $request->validate([
            'sp_subject_id' => ['required', 'integer'], 'term' => ['nullable', 'in:first,second'],
            'weeks' => ['nullable', 'array'], 'weeks.*' => ['integer'], 'grade_levels' => ['nullable', 'array'], 'grade_levels.*' => ['integer'],
            'range_start' => ['nullable', 'integer', 'min:1'], 'range_end' => ['nullable', 'integer', 'min:1'],
        ]);
        $subject = SpSubject::where('program', $program)->findOrFail($data['sp_subject_id']);
        $filters = array_filter([
            'terms' => ! empty($data['term']) ? [$data['term'], 'both'] : null,
            'weeks' => $data['weeks'] ?? null, 'grade_levels' => $data['grade_levels'] ?? null,
            'order_range' => isset($data['range_start'], $data['range_end']) ? [(int) $data['range_start'], (int) $data['range_end']] : null,
        ]);
        $stats = $this->pool->stats($subject->id, $filters);
        $weeks = $this->settings->weeksCount($this->scopedSchoolId());
        $total = $this->pool->stats($subject->id, [])['total'];

        return response()->json($stats + ['bank_total' => $total, 'weeks_count' => $weeks, 'weekly_count' => (int) ceil($total / max($weeks, 1))]);
    }
}
