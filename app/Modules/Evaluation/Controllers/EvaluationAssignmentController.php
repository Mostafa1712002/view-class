<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\User;
use App\Modules\Evaluation\Actions\AssignEvaluators;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Task 7 — assign evaluator users to a form and scope each to a subset of targets.
 */
class EvaluationAssignmentController extends Controller
{
    use HasSchoolScope;

    /**
     * Roles that belong to the end-user side (teacher/student/parent) — never
     * eligible as evaluators. Evaluators are administrative-side accounts
     * (super-admin, school-admin, and any supervisor/manager role), the same
     * definition AdminController uses for "حسابات الجانب الاداري".
     */
    private const END_USER_ROLES = ['teacher', 'student', 'parent'];

    public function __construct(
        private readonly EvaluationFormRepository $forms,
        private readonly AssignEvaluators $assign,
    ) {
    }

    /** Evaluators management page. */
    public function index(int $form): View|RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }

        $targets = $evForm->targets()
            ->where('target_type', 'user')
            ->with('subject:id,name')
            ->get()
            ->map(fn ($t) => [
                'target_id' => $t->id,
                'user_id'   => $t->target_id,
                'name'      => $t->subject?->name ?? ('#'.$t->target_id),
            ]);

        $assignments = $evForm->assignments()
            ->with(['evaluator:id,name,username', 'targets:id,target_id'])
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'evaluator_id' => $a->evaluator_id,
                'name'         => $a->evaluator?->name ?? ('#'.$a->evaluator_id),
                'username'     => $a->evaluator?->username,
                'target_ids'   => $a->targets->pluck('id')->all(),
                'target_count' => $a->targets->count(),
            ]);

        $evaluators = $this->evaluatorCandidates($evForm);

        return view('admin.evaluation.evaluators.index', [
            'form'        => $evForm,
            'targets'     => $targets,
            'assignments' => $assignments,
            'evaluators'  => $evaluators,
            'allowSelf'   => (bool) $evForm->setting('allow_self_eval', false),
        ]);
    }

    public function store(Request $request, int $form): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }

        $data = $request->validate([
            'evaluator_ids'   => ['required', 'array', 'min:1'],
            'evaluator_ids.*' => ['integer'],
            'target_ids'      => ['required', 'array', 'min:1'],
            'target_ids.*'    => ['integer'],
        ]);

        // Restrict to admin-side candidates scoped to this school — silently drops
        // anything not eligible (teacher/student/parent or another school).
        $allowed = $this->evaluatorCandidates($evForm)->pluck('id')->all();
        $evaluatorIds = array_values(array_intersect(
            array_map('intval', array_unique($data['evaluator_ids'])),
            $allowed
        ));

        if (empty($evaluatorIds)) {
            return back()->withErrors(['evaluator_ids' => __('evaluation.evaluators.errors.user_not_found')])->withInput();
        }

        // One assignment per evaluator against the chosen targets. Already-assigned
        // evaluators are reused (unique form_id+evaluator_id) instead of erroring.
        $assigned = 0;
        $failures = [];
        foreach ($evaluatorIds as $evaluatorId) {
            try {
                $this->assign->assign($evForm, $evaluatorId, $data['target_ids'], (int) auth()->id());
                $assigned++;
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ((array) $messages as $m) {
                        $failures[] = $m;
                    }
                }
            }
        }

        if ($assigned === 0) {
            return back()->withErrors(['evaluator_ids' => $failures ?: [__('evaluation.evaluators.errors.user_not_found')]])->withInput();
        }

        $redirect = redirect()
            ->route('admin.evaluations.evaluators.index', $evForm->id)
            ->with('status', __('evaluation.evaluators.flash.assigned', ['count' => $assigned]));

        if (!empty($failures)) {
            $redirect->with('error', implode(' ', array_unique($failures)));
        }

        return $redirect;
    }

    public function update(Request $request, int $form, int $assignment): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }
        $model = $evForm->assignments()->whereKey($assignment)->first();
        if (!$model) {
            return back()->with('error', __('evaluation.evaluators.errors.assignment_not_found'));
        }

        $data = $request->validate([
            'target_ids'   => ['required', 'array', 'min:1'],
            'target_ids.*' => ['integer'],
        ]);

        try {
            $this->assign->assign($evForm, (int) $model->evaluator_id, $data['target_ids'], (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('admin.evaluations.evaluators.index', $evForm->id)
            ->with('status', __('evaluation.evaluators.flash.updated'));
    }

    public function destroy(int $form, int $assignment): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }

        $this->assign->remove($evForm, $assignment, (int) auth()->id());

        return back()->with('status', __('evaluation.evaluators.flash.removed'));
    }

    /** Users who may be picked as evaluators, scoped to the form's school. */
    private function evaluatorCandidates(EvaluationForm $form)
    {
        $schoolId = $this->scopeSchool($form);

        return User::query()
            ->whereHas('roles', fn ($r) => $r->whereNotIn('slug', self::END_USER_ROLES))
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('slug', self::END_USER_ROLES))
            ->when($schoolId !== null, fn ($q) => $q->where('users.school_id', $schoolId))
            ->orderBy('users.name')
            ->limit(500)
            ->get(['id', 'name', 'username'])
            ->map(fn (User $u) => [
                'id'    => $u->id,
                'label' => $u->name.($u->username ? " ({$u->username})" : ''),
            ]);
    }

    private function scopeSchool(EvaluationForm $form): ?int
    {
        $auth = auth()->user();
        if ($auth && !$auth->isSuperAdmin()) {
            return (int) $auth->school_id ?: null;
        }

        return $form->school_id;
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
