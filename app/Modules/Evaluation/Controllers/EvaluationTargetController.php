<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Evaluation\Actions\SetEvaluationTargets;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Task 6 — choose WHO is evaluated by a form (targets).
 */
class EvaluationTargetController extends Controller
{
    use HasSchoolScope;

    /** Roles eligible to be targeted, by usage domain. */
    private const DOMAIN_ROLES = [
        'teacher'         => ['teacher'],
        'admin'           => ['school-admin'],
        'job_performance' => ['teacher', 'school-admin'],
    ];

    private const DEFAULT_ROLES = ['teacher', 'school-admin'];

    public function __construct(
        private readonly EvaluationFormRepository $forms,
        private readonly SetEvaluationTargets $setTargets,
    ) {
    }

    /** Targets management page. */
    public function index(Request $request, int $form): View|RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }

        $schoolId = $this->scopeSchool($request, $evForm);

        $candidates = $this->candidateQuery($request, $evForm, $schoolId)
            ->with(['roles:id,name,slug', 'school:id,name', 'subjects:id,name'])
            ->orderBy('users.name')
            ->limit(300)
            ->get()
            ->map(fn (User $u) => $this->describe($u));

        $targets = $evForm->targets()
            ->where('target_type', 'user')
            ->with(['subject:id,name,school_id'])
            ->get()
            ->map(fn ($t) => $this->describeTarget($t));

        return view('admin.evaluation.targets.index', [
            'form'       => $evForm,
            'candidates' => $candidates,
            'targets'    => $targets,
            'schools'    => $this->schoolsForFilter(),
            'sections'   => $this->sectionsForFilter($schoolId),
            'subjects'   => $this->subjectsForFilter($schoolId),
            'roles'      => $this->roleOptions($evForm),
            'filters'    => [
                'school_id'  => $request->integer('school_id') ?: null,
                'section_id' => $request->integer('section_id') ?: null,
                'subject_id' => $request->integer('subject_id') ?: null,
                'role'       => $request->string('role')->toString() ?: null,
                'q'          => $request->string('q')->toString(),
            ],
        ]);
    }

    /** Persist the chosen users (individuals + bulk-set expansions). */
    public function store(Request $request, int $form): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }

        $data = $request->validate([
            'user_ids'   => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'bulk_role'  => ['nullable', 'in:teacher,school-admin'],
            'bulk_subject_id' => ['nullable', 'integer'],
            'bulk_school_id'  => ['nullable', 'integer'],
        ]);

        $userIds = $data['user_ids'] ?? [];

        // Expand bulk sets ("all teachers of school X" / "all teachers of subject Y").
        if (!empty($data['bulk_role']) || !empty($data['bulk_subject_id'])) {
            $userIds = array_merge($userIds, $this->expandBulk($request, $evForm, $data));
        }

        $userIds = array_values(array_unique(array_map('intval', array_filter($userIds))));
        if ($userIds === []) {
            return back()->with('error', __('evaluation.targets.flash.none_selected'));
        }

        $added = $this->setTargets->add($evForm, $userIds, (int) auth()->id());

        $msg = $added > 0
            ? __('evaluation.targets.flash.added', ['count' => $added])
            : __('evaluation.targets.flash.all_existing');

        return redirect()
            ->route('admin.evaluations.targets.index', $evForm->id)
            ->with('status', $msg);
    }

    public function destroy(int $form, int $target): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }

        $this->setTargets->remove($evForm, $target, (int) auth()->id());

        return back()->with('status', __('evaluation.targets.flash.removed'));
    }

    /** Pre-save summary (AJAX): counts, schools, subjects, duplicates, inactive. */
    public function summary(Request $request, int $form): JsonResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return response()->json(['success' => false], 404);
        }

        $userIds = array_values(array_unique(array_map('intval', array_filter((array) $request->input('user_ids', [])))));

        $existing = $evForm->targets()->where('target_type', 'user')->pluck('target_id')->map(fn ($i) => (int) $i)->all();

        $users = User::query()
            ->whereIn('id', $userIds)
            ->with(['school:id,name', 'subjects:id,name'])
            ->get();

        $duplicates = array_values(array_intersect($userIds, $existing));
        $inactive   = $users->filter(fn (User $u) => $this->isInactive($u))->count();
        $schools    = $users->pluck('school.name')->filter()->unique()->values();
        $subjects   = $users->flatMap(fn (User $u) => $u->subjects->pluck('name'))->filter()->unique()->values();

        return response()->json([
            'success'    => true,
            'selected'   => count($userIds),
            'new'        => count(array_diff($userIds, $existing)),
            'duplicates' => count($duplicates),
            'inactive'   => $inactive,
            'schools'    => $schools,
            'subjects'   => $subjects,
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** @return int[] */
    private function expandBulk(Request $request, EvaluationForm $form, array $data): array
    {
        $schoolId = $data['bulk_school_id'] ?? $this->scopeSchool($request, $form);

        $q = User::query();

        if (!empty($data['bulk_role'])) {
            $q->whereHas('roles', fn ($r) => $r->where('slug', $data['bulk_role']));
        } else {
            // subject-only bulk implies teachers
            $q->whereHas('roles', fn ($r) => $r->where('slug', 'teacher'));
        }

        if ($schoolId !== null) {
            $q->where('users.school_id', $schoolId);
        }
        if (!empty($data['bulk_subject_id'])) {
            $q->whereHas('subjects', fn ($s) => $s->where('subjects.id', (int) $data['bulk_subject_id']));
        }

        return $q->pluck('users.id')->map(fn ($i) => (int) $i)->all();
    }

    private function candidateQuery(Request $request, EvaluationForm $form, ?int $schoolId): Builder
    {
        $allowedRoles = $this->allowedRoles($form);

        $role = $request->string('role')->toString();
        $roles = ($role && in_array($role, $allowedRoles, true)) ? [$role] : $allowedRoles;

        $q = User::query()->whereHas('roles', fn ($r) => $r->whereIn('slug', $roles));

        if ($schoolId !== null) {
            $q->where('users.school_id', $schoolId);
        }
        if ($sid = $request->integer('section_id')) {
            $q->where('users.section_id', $sid);
        }
        if ($subjectId = $request->integer('subject_id')) {
            $q->whereHas('subjects', fn ($s) => $s->where('subjects.id', $subjectId));
        }
        if ($search = trim($request->string('q')->toString())) {
            $q->where(function ($w) use ($search) {
                $w->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.username', 'like', "%{$search}%");
            });
        }

        return $q;
    }

    /** @return string[] */
    private function allowedRoles(EvaluationForm $form): array
    {
        return self::DOMAIN_ROLES[$form->usage_domain?->value] ?? self::DEFAULT_ROLES;
    }

    private function roleOptions(EvaluationForm $form): array
    {
        $out = [];
        foreach ($this->allowedRoles($form) as $slug) {
            $out[$slug] = __('evaluation.targets.role_'.str_replace('-', '_', $slug));
        }

        return $out;
    }

    private function describe(User $u): array
    {
        return [
            'id'       => $u->id,
            'name'     => $u->name,
            'username' => $u->username,
            'role'     => optional($u->roles->first())->name,
            'school'   => optional($u->school)->name,
            'subjects' => $u->subjects->pluck('name')->implode('، '),
            'inactive' => $this->isInactive($u),
        ];
    }

    private function describeTarget($target): array
    {
        $user = $target->subject;

        return [
            'target_id' => $target->id,
            'user_id'   => $target->target_id,
            'name'      => $user?->name ?? ('#'.$target->target_id),
            'school'    => optional($user?->school)->name,
            'after_publish' => (bool) $target->added_after_publish,
        ];
    }

    private function isInactive(User $u): bool
    {
        foreach (['is_active', 'active', 'status'] as $col) {
            if (\Schema::hasColumn('users', $col)) {
                $val = $u->{$col};
                if ($col === 'status') {
                    return in_array((string) $val, ['inactive', 'suspended', 'disabled', '0'], true);
                }

                return !$val;
            }
        }

        return false;
    }

    private function scopeSchool(Request $request, EvaluationForm $form): ?int
    {
        $auth = auth()->user();
        if ($auth && !$auth->isSuperAdmin()) {
            return (int) $auth->school_id ?: null;
        }
        // super-admin: explicit filter, else fall back to the form's own school.
        return $request->integer('school_id') ?: $form->school_id;
    }

    private function schoolsForFilter()
    {
        $auth = auth()->user();
        if ($auth && $auth->isSuperAdmin()) {
            return School::query()->orderBy('name')->get(['id', 'name']);
        }

        return collect();
    }

    private function sectionsForFilter(?int $schoolId)
    {
        $q = Section::query();
        if ($schoolId) {
            $q->where('school_id', $schoolId);
        }

        return $q->orderBy('name')->get(['id', 'name', 'school_id']);
    }

    private function subjectsForFilter(?int $schoolId)
    {
        $q = Subject::query();
        if ($schoolId && \Schema::hasColumn('subjects', 'school_id')) {
            $q->where(fn ($w) => $w->where('school_id', $schoolId)->orWhereNull('school_id'));
        }

        return $q->orderBy('name')->get(['id', 'name']);
    }

    private function resolveForm(int $form): EvaluationForm|RedirectResponse
    {
        $evForm = $this->forms->findScoped($form, $this->activeSchoolId());
        if (!$evForm) {
            return redirect()->route('admin.evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        return $evForm;
    }
}
