<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\EvaluationForm;
use App\Models\SchedulePeriod;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Evaluation\Actions\ExecuteClassVisit;
use App\Modules\Evaluation\Actions\ScheduleClassVisit;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\FormStatus;
use App\Modules\Evaluation\Enums\VisitStatus;
use App\Modules\Evaluation\Repositories\Contracts\ClassVisitRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sprint 8 Tasks 16–18 — class visits (الزيارات الصفية).
 *
 * 16 list/filter, 17 schedule/edit/delete, 18 execute (→ existing execution UI).
 */
class ClassVisitController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly ClassVisitRepository $visits,
        private readonly ScheduleClassVisit $scheduler,
        private readonly ExecuteClassVisit $executor,
    ) {
    }

    /** Task 16 — list with filters + KPIs + statuses. */
    public function index(Request $request): View
    {
        // Super-admin may override the active school via the filter dropdown.
        $schoolId = $this->resolveFilterSchool($request);

        $filters = [
            'teacher_id'    => $request->integer('teacher_id') ?: null,
            'subject_id'    => $request->integer('subject_id') ?: null,
            'supervisor_id' => $request->integer('supervisor_id') ?: null,
            'class_room_id' => $request->integer('class_room_id') ?: null,
            'section_id'    => $request->integer('section_id') ?: null,
            'status'        => $request->string('status')->toString() ?: null,
            'date_from'     => $request->date('date_from')?->toDateString(),
            'date_to'       => $request->date('date_to')?->toDateString(),
            'search'        => $request->string('q')->toString() ?: null,
        ];

        $visits = $this->visits->paginate(
            $schoolId,
            array_filter($filters, fn ($v) => $v !== null),
            25
        );

        // Eager-load the linked evaluation/section/class so the table can derive
        // the effective (completion) status at read time without N+1.
        $visits->getCollection()->load(['evaluation:id,status', 'section:id,name', 'classRoom:id,name']);

        return view('admin.class-visits.index', [
            'visits'      => $visits,
            'filters'     => $filters,
            'stats'       => $this->stats($schoolId),
            'statuses'    => VisitStatus::options(),
            'schools'     => $this->schoolsForFilter(),
            'teachers'    => $this->teachers($schoolId),
            'supervisors' => $this->supervisors($schoolId),
            'subjects'    => $this->subjects($schoolId),
            'sections'    => $this->sections($schoolId),
            'classes'     => $this->classes($schoolId),
            'effective'   => fn ($visit) => $this->effectiveStatus($visit),
        ]);
    }

    /** Task 17 — schedule form. */
    public function create(): View
    {
        return view('admin.class-visits.create', $this->formViewData(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $eligible = $this->eligibleFormIds($this->activeSchoolId());
        if (!in_array((int) $data['form_id'], $eligible, true)) {
            return back()->withErrors(['form_id' => __('class_visits.errors.form_not_eligible')])->withInput();
        }
        if (!$this->periodBelongsToTeacher($data)) {
            return back()->withErrors(['period_id' => __('class_visits.errors.period_not_teacher')])->withInput();
        }

        try {
            $this->scheduler->create($data, $this->activeSchoolId(), (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.class-visits.index')->with('status', __('class_visits.flash.created'));
    }

    /** Task 17 — edit (blocked once completed). */
    public function edit(int $id): View|RedirectResponse
    {
        $visit = $this->visits->findScoped($id, $this->activeSchoolId());
        if (!$visit) {
            return redirect()->route('admin.class-visits.index')->with('error', __('class_visits.flash.not_found'));
        }
        if ($visit->status === VisitStatus::Completed) {
            return redirect()->route('admin.class-visits.index')->with('error', __('class_visits.errors.completed_locked'));
        }

        return view('admin.class-visits.edit', $this->formViewData($visit));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $visit = $this->visits->findScoped($id, $this->activeSchoolId());
        if (!$visit) {
            return redirect()->route('admin.class-visits.index')->with('error', __('class_visits.flash.not_found'));
        }

        $data = $this->validateData($request);

        $eligible = $this->eligibleFormIds($this->activeSchoolId());
        if (!in_array((int) $data['form_id'], $eligible, true)) {
            return back()->withErrors(['form_id' => __('class_visits.errors.form_not_eligible')])->withInput();
        }
        if (!$this->periodBelongsToTeacher($data)) {
            return back()->withErrors(['period_id' => __('class_visits.errors.period_not_teacher')])->withInput();
        }

        try {
            $this->scheduler->update($visit, $data, $this->activeSchoolId(), (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.class-visits.index')->with('status', __('class_visits.flash.updated'));
    }

    /** Task 17 — soft delete (blocked once completed). */
    public function destroy(int $id): RedirectResponse
    {
        $visit = $this->visits->findScoped($id, $this->activeSchoolId());
        if (!$visit) {
            return redirect()->route('admin.class-visits.index')->with('error', __('class_visits.flash.not_found'));
        }
        if ($visit->status === VisitStatus::Completed) {
            return back()->with('error', __('class_visits.errors.delete_blocked'));
        }

        $this->visits->delete($visit);

        return redirect()->route('admin.class-visits.index')->with('status', __('class_visits.flash.deleted'));
    }

    /** Task 18 — execute the visit (opens the linked form via the execution UI). */
    public function execute(int $id): RedirectResponse
    {
        $visit = $this->visits->findScoped($id, $this->activeSchoolId());
        if (!$visit) {
            return redirect()->route('admin.class-visits.index')->with('error', __('class_visits.flash.not_found'));
        }

        if ($visit->status === VisitStatus::Cancelled) {
            return back()->with('error', __('class_visits.errors.cannot_execute_cancelled'));
        }

        // Cannot execute before the visit date — except super-admin / school-admin.
        $user = auth()->user();
        $privileged = $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());
        if (!$privileged && $visit->visit_date && $visit->visit_date->isFuture()
            && $visit->visit_date->startOfDay()->gt(now()->startOfDay())) {
            return back()->with('error', __('class_visits.errors.before_date'));
        }

        try {
            $evaluation = $this->executor->execute($visit, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.execute.show', $evaluation->id);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Validate the schedule/update payload. */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'teacher_id'    => ['required', 'integer', 'exists:users,id'],
            'form_id'       => ['required', 'integer', 'exists:evaluation_forms,id'],
            'subject_id'    => ['nullable', 'integer'],
            'stage_id'      => ['nullable', 'integer'],
            'class_room_id' => ['nullable', 'integer'],
            'section_id'    => ['nullable', 'integer'],
            'period_id'     => ['nullable', 'integer'],
            'visit_date'    => ['required', 'date'],
            'visit_time'    => ['nullable', 'date_format:H:i'],
            'visit_type'    => ['required', 'in:announced,secret'],
            'notify_teacher' => ['nullable', 'boolean'],
            'pre_notes'     => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /** Timetable guard: if a period is chosen it must belong to the teacher. */
    private function periodBelongsToTeacher(array $data): bool
    {
        $periodId = isset($data['period_id']) && $data['period_id'] !== '' ? (int) $data['period_id'] : null;
        if ($periodId === null) {
            return true; // no-period visits are allowed
        }

        return SchedulePeriod::query()
            ->whereKey($periodId)
            ->where('teacher_id', (int) $data['teacher_id'])
            ->exists();
    }

    /** Effective status: a visit whose evaluation is completed/approved reads as completed. */
    private function effectiveStatus($visit): VisitStatus
    {
        $current = $visit->status instanceof VisitStatus ? $visit->status : VisitStatus::Scheduled;
        $evalStatus = $visit->evaluation?->status;
        if ($evalStatus instanceof EvaluationStatus
            && in_array($evalStatus, [EvaluationStatus::Completed, EvaluationStatus::Approved], true)) {
            return VisitStatus::Completed;
        }

        return $current;
    }

    /** Shared data for create/edit views. */
    private function formViewData($visit): array
    {
        $schoolId = $this->activeSchoolId();

        return [
            'visit'    => $visit,
            'schools'  => $this->schoolsForFilter(),
            'teachers' => $this->teachers($schoolId),
            'subjects' => $this->subjects($schoolId),
            'sections' => $this->sections($schoolId),
            'classes'  => $this->classes($schoolId),
            'forms'    => $this->eligibleForms($schoolId),
            'periods'  => $visit && $visit->teacher_id ? $this->periodsForTeacher((int) $visit->teacher_id) : collect(),
            'types'    => [
                'announced' => __('class_visits.visit_type.announced'),
                'secret'    => __('class_visits.visit_type.secret'),
            ],
        ];
    }

    /** Super-admin can pick any school; everyone else is pinned to their own. */
    private function resolveFilterSchool(Request $request): ?int
    {
        $auth = auth()->user();
        if ($auth && $auth->isSuperAdmin()) {
            return $request->integer('school_id') ?: $this->activeSchoolId();
        }

        return $this->activeSchoolId();
    }

    /**
     * KPI tiles — eval-aware so they agree with the table's effective status:
     * a visit whose linked evaluation is completed/approved counts as completed.
     */
    private function stats(?int $schoolId): array
    {
        $base = fn (): Builder => \App\Models\ClassVisit::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId));

        $evalDone = ['completed', 'approved'];
        $hasDoneEval = fn (Builder $q) => $q->whereHas('evaluation', fn ($e) => $e->whereIn('status', $evalDone));

        $completed = (clone $base())
            ->where(fn (Builder $q) => $q->where('status', VisitStatus::Completed->value)->orWhere($hasDoneEval))
            ->count();

        // In-progress excludes rows whose evaluation already reads as completed.
        $inProgress = (clone $base())
            ->where('status', VisitStatus::InProgress->value)
            ->whereDoesntHave('evaluation', fn ($e) => $e->whereIn('status', $evalDone))
            ->count();

        return [
            'total'       => (clone $base())->count(),
            'scheduled'   => (clone $base())->where('status', VisitStatus::Scheduled->value)->count(),
            'in_progress' => $inProgress,
            'completed'   => $completed,
        ];
    }

    /** Published, class-visit-only forms within scope (incl. global school_id=null). */
    private function eligibleForms(?int $schoolId)
    {
        return $this->eligibleFormsQuery($schoolId)->orderBy('title')->get(['id', 'title']);
    }

    /** @return int[] */
    private function eligibleFormIds(?int $schoolId): array
    {
        return $this->eligibleFormsQuery($schoolId)->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    private function eligibleFormsQuery(?int $schoolId): Builder
    {
        return EvaluationForm::query()
            ->where('status', FormStatus::Published->value)
            ->where('is_class_visit_only', true)
            ->when(
                $schoolId !== null,
                fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('school_id', $schoolId)->orWhereNull('school_id'))
            );
    }

    private function periodsForTeacher(int $teacherId)
    {
        return SchedulePeriod::query()
            ->where('teacher_id', $teacherId)
            ->with('subject:id,name')
            ->ordered()
            ->get();
    }

    private function teachers(?int $schoolId)
    {
        return $this->usersByRole('teacher', $schoolId);
    }

    private function supervisors(?int $schoolId)
    {
        return User::query()
            ->whereHas('roles', fn ($r) => $r->whereIn('slug', ['school-admin', 'super-admin']))
            ->when($schoolId !== null, fn ($q) => $q->where('users.school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function usersByRole(string $role, ?int $schoolId)
    {
        return User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', $role))
            ->when($schoolId !== null, fn ($q) => $q->where('users.school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function subjects(?int $schoolId)
    {
        return Subject::query()
            ->when($schoolId !== null && \Schema::hasColumn('subjects', 'school_id'),
                fn ($q) => $q->where(fn ($w) => $w->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function sections(?int $schoolId)
    {
        return Section::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function classes(?int $schoolId)
    {
        return ClassRoom::query()
            ->when($schoolId !== null, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
            ->orderBy('name')
            ->get(['id', 'name', 'section_id']);
    }

    private function schoolsForFilter()
    {
        $auth = auth()->user();
        if ($auth && $auth->isSuperAdmin()) {
            return School::query()->orderBy('name')->get(['id', 'name']);
        }

        return collect();
    }
}
