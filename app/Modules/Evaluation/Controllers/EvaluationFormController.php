<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Modules\Evaluation\Actions\PublishEvaluationForm;
use App\Modules\Evaluation\Actions\SaveEvaluationForm;
use App\Modules\Evaluation\Enums\FormStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Enums\UsageDomain;
use App\Modules\Evaluation\Http\Requests\EvaluationFormRequest;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\FormCompletenessChecker;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EvaluationFormController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly EvaluationFormRepository $forms,
        private readonly SaveEvaluationForm $saveForm,
        private readonly AuditTrail $audit,
        private readonly FormCompletenessChecker $checker,
        private readonly PublishEvaluationForm $publisher,
    ) {
    }

    /** Task 1 — evaluation forms management list. */
    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();

        $filters = [
            'type'                     => $request->string('type')->toString() ?: null,
            'usage_domain'             => $request->string('usage_domain')->toString() ?: null,
            'status'                   => $request->string('status')->toString() ?: null,
            'is_class_visit_only'      => $this->ternary($request->input('is_class_visit_only')),
            'links_to_job_performance' => $this->ternary($request->input('links_to_job_performance')),
            'created_from'             => $request->date('created_from')?->toDateString(),
            'created_to'               => $request->date('created_to')?->toDateString(),
            'search'                   => $request->string('q')->toString() ?: null,
        ];

        $forms = $this->forms->paginate($schoolId, array_filter($filters, fn ($v) => $v !== null), 25);

        return view('admin.evaluation.forms.index', [
            'forms'   => $forms,
            'filters' => $filters,
            'stats'   => $this->stats($schoolId),
            'types'   => FormType::options(),
            'domains' => UsageDomain::options(),
            'statuses'=> FormStatus::options(),
        ]);
    }

    /** Task 2 — create form. */
    public function create(): View
    {
        return view('admin.evaluation.forms.create', $this->formViewData());
    }

    public function store(EvaluationFormRequest $request): RedirectResponse
    {
        $form = $this->saveForm->execute(null, $request->validated(), $this->activeSchoolId(), (int) auth()->id());

        return $this->afterSave($request, $form, __('evaluation.form.created'));
    }

    /** Task 2 — edit basic data. */
    public function edit(int $id): View|RedirectResponse
    {
        $form = $this->forms->findScoped($id, $this->activeSchoolId());
        if (!$form) {
            return redirect()->route('admin.evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        return view('admin.evaluation.forms.edit', $this->formViewData($form));
    }

    public function update(EvaluationFormRequest $request, int $id): RedirectResponse
    {
        $form = $this->forms->findScoped($id, $this->activeSchoolId());
        if (!$form) {
            return redirect()->route('admin.evaluations.index')->with('error', __('evaluation.form.not_found'));
        }
        $form = $this->saveForm->execute($form, $request->validated(), $this->activeSchoolId(), (int) auth()->id());

        return $this->afterSave($request, $form, __('evaluation.form.updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $form = $this->forms->findScoped($id, $this->activeSchoolId());
        if (!$form) {
            return redirect()->route('admin.evaluations.index')->with('error', __('evaluation.form.not_found'));
        }
        // Cannot delete a form already used in real evaluations — archive instead (Task 1 rule).
        if ($form->evaluations()->exists()) {
            return back()->with('error', __('evaluation.form.delete_blocked'));
        }
        $this->audit->deleted($form, "حذف نموذج تقييم: {$form->title}");
        $this->forms->delete($form);

        return redirect()->route('admin.evaluations.index')->with('status', __('evaluation.form.deleted'));
    }

    /** Task 8 — confirmation screen before publishing (completeness + summary). */
    public function publishConfirm(int $id): View|RedirectResponse
    {
        $form = $this->forms->findScoped($id, $this->activeSchoolId());
        if (!$form) {
            return redirect()->route('admin.evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        $problems = $this->checker->problems($form);

        return view('admin.evaluation.publish.confirm', [
            'form'     => $form,
            'problems' => $problems,
            'summary'  => [
                'items'      => $form->items()->where('status', 'active')->count(),
                'indicators' => $form->indicators()->where('status', 'active')->count(),
                'targets'    => $form->targets()->count(),
                'evaluators' => $form->assignments()->count(),
                'close_date' => $form->close_date,
                'notify'     => (bool) $form->setting('notify_on_publish', true),
            ],
        ]);
    }

    /** Task 8 — freeze a snapshot, lock structure, notify evaluators. */
    public function publish(int $id): RedirectResponse
    {
        $form = $this->forms->findScoped($id, $this->activeSchoolId());
        if (!$form) {
            return redirect()->route('admin.evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        try {
            $snapshot = $this->publisher->publish($form, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('admin.evaluations.index')
            ->with('status', __('evaluation.publish.flash.published', ['version' => $snapshot->version]));
    }

    public function close(int $id): RedirectResponse
    {
        $form = $this->forms->findScoped($id, $this->activeSchoolId());
        if (!$form) {
            return redirect()->route('admin.evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        try {
            $this->publisher->close($form, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', __('evaluation.publish.flash.closed'));
    }

    public function archive(int $id): RedirectResponse
    {
        $form = $this->forms->findScoped($id, $this->activeSchoolId());
        if (!$form) {
            return redirect()->route('admin.evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        try {
            $this->publisher->archive($form, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', __('evaluation.publish.flash.archived'));
    }

    /** Shared data for create/edit views. */
    private function formViewData(?EvaluationForm $form = null): array
    {
        return [
            'form'     => $form,
            'types'    => FormType::options(),
            'domains'  => UsageDomain::options(),
            'levels'   => $form ? $form->levels->pluck('label')->all() : [],
        ];
    }

    /** "Save" vs "Save & continue to items" routing. */
    private function afterSave(Request $request, EvaluationForm $form, string $message): RedirectResponse
    {
        if ($request->input('after') === 'items' && \Illuminate\Support\Facades\Route::has('admin.evaluations.items.index')) {
            return redirect()->route('admin.evaluations.items.index', $form)->with('status', $message);
        }

        return redirect()->route('admin.evaluations.edit', $form)->with('status', $message);
    }

    /** KPI tiles: counts by status within scope. */
    private function stats(?int $schoolId): array
    {
        $base = fn (): Builder => EvaluationForm::query()->when(
            $schoolId !== null,
            fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('school_id', $schoolId)->orWhereNull('school_id'))
        );

        return [
            'total'     => (clone $base())->count(),
            'published' => (clone $base())->where('status', FormStatus::Published->value)->count(),
            'draft'     => (clone $base())->where('status', FormStatus::Draft->value)->count(),
            'closed'    => (clone $base())->where('status', FormStatus::Closed->value)->count(),
        ];
    }

    /** Tri-state filter: '' / '1' / '0' -> null / true / false. */
    private function ternary(mixed $v): ?bool
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (bool) $v;
    }
}
