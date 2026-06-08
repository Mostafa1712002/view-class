<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\EvaluationIndicator;
use App\Models\EvaluationItem;
use App\Models\EvaluationResponse;
use App\Modules\Evaluation\Actions\SaveEvaluationIndicator;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationIndicatorController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly EvaluationFormRepository $forms,
        private readonly SaveEvaluationIndicator $saveIndicator,
        private readonly AuditTrail $audit,
    ) {
    }

    /** Task 5 — list an item's indicators. */
    public function index(int $form, int $item): View|RedirectResponse
    {
        $ctx = $this->resolve($form, $item);
        if (!is_array($ctx)) {
            return $ctx;
        }
        [$evForm, $evItem] = $ctx;

        return view('admin.evaluation.indicators.index', [
            'form'       => $evForm,
            'item'       => $evItem,
            'indicators' => $evItem->indicators()->with('level')->orderBy('sort_order')->get(),
            'isRubric'   => $evForm->type === FormType::Rubric,
            'levels'     => $evForm->type === FormType::Rubric ? $evForm->levels : collect(),
        ]);
    }

    public function store(Request $request, int $form, int $item): RedirectResponse
    {
        $ctx = $this->resolve($form, $item);
        if (!is_array($ctx)) {
            return $ctx;
        }
        [$evForm, $evItem] = $ctx;
        if ($lock = $this->guardEditable($evForm)) {
            return $lock;
        }

        $this->saveIndicator->execute($evForm, $evItem, null, $this->validatedData($request));

        return redirect()->route('admin.evaluations.indicators.index', [$evForm->id, $evItem->id])
            ->with('status', __('evaluation_items.messages.indicator_created'));
    }

    public function update(Request $request, int $form, int $item, int $indicator): RedirectResponse
    {
        $ctx = $this->resolve($form, $item);
        if (!is_array($ctx)) {
            return $ctx;
        }
        [$evForm, $evItem] = $ctx;
        if ($lock = $this->guardEditable($evForm)) {
            return $lock;
        }
        $model = $evItem->indicators()->find($indicator);
        if (!$model) {
            return back()->with('error', __('evaluation_items.messages.indicator_not_found'));
        }

        $this->saveIndicator->execute($evForm, $evItem, $model, $this->validatedData($request));

        return redirect()->route('admin.evaluations.indicators.index', [$evForm->id, $evItem->id])
            ->with('status', __('evaluation_items.messages.indicator_updated'));
    }

    public function toggle(int $form, int $item, int $indicator): RedirectResponse
    {
        $ctx = $this->resolve($form, $item);
        if (!is_array($ctx)) {
            return $ctx;
        }
        [, $evItem] = $ctx;
        $model = $evItem->indicators()->find($indicator);
        if (!$model) {
            return back()->with('error', __('evaluation_items.messages.indicator_not_found'));
        }

        $old = $model->toArray();
        $model->status = $model->status === 'active' ? 'disabled' : 'active';
        $model->save();
        $this->audit->updated($model, "تغيير حالة مؤشر: {$model->text}", $old);

        $key = $model->status === 'active' ? 'indicator_enabled' : 'indicator_disabled';

        return back()->with('status', __('evaluation_items.messages.'.$key));
    }

    public function destroy(int $form, int $item, int $indicator): RedirectResponse
    {
        $ctx = $this->resolve($form, $item);
        if (!is_array($ctx)) {
            return $ctx;
        }
        [$evForm, $evItem] = $ctx;
        if ($lock = $this->guardEditable($evForm)) {
            return $lock;
        }
        $model = $evItem->indicators()->find($indicator);
        if (!$model) {
            return back()->with('error', __('evaluation_items.messages.indicator_not_found'));
        }

        if (EvaluationResponse::where('indicator_id', $model->id)->exists()) {
            return back()->with('error', __('evaluation_items.messages.delete_blocked_indicator'));
        }

        $this->audit->deleted($model, "حذف مؤشر: {$model->text}");
        $model->delete();

        return back()->with('status', __('evaluation_items.messages.indicator_deleted'));
    }

    public function reorder(Request $request, int $form, int $item): JsonResponse
    {
        $ctx = $this->resolve($form, $item);
        if (!is_array($ctx)) {
            return response()->json(['success' => false], 404);
        }
        [, $evItem] = $ctx;
        $request->validate(['indicators' => 'required|array', 'indicators.*' => 'integer']);

        foreach ($request->input('indicators') as $index => $indId) {
            $evItem->indicators()->whereKey((int) $indId)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'text'              => ['required', 'string', 'max:1000'],
            'description'       => ['nullable', 'string'],
            'level_id'          => ['nullable', 'integer'],
            'is_required'       => ['nullable', 'boolean'],
            'needs_note'        => ['nullable', 'boolean'],
            'needs_evidence'    => ['nullable', 'boolean'],
            'evidence_required' => ['nullable', 'boolean'],
            'status'            => ['nullable', 'in:active,disabled'],
        ]);
    }

    /**
     * Resolve form (tenant-scoped) + item (form-owned).
     *
     * @return array{0: EvaluationForm, 1: EvaluationItem}|RedirectResponse
     */
    private function resolve(int $form, int $item): array|RedirectResponse
    {
        $evForm = $this->forms->findScoped($form, $this->activeSchoolId());
        if (!$evForm) {
            return redirect()->route('admin.evaluations.index')
                ->with('error', __('evaluation_items.messages.form_not_found'));
        }
        $evItem = $evForm->items()->find($item);
        if (!$evItem) {
            return redirect()->route('admin.evaluations.items.index', $evForm->id)
                ->with('error', __('evaluation_items.messages.item_not_found'));
        }

        return [$evForm, $evItem];
    }

    private function guardEditable(EvaluationForm $form): ?RedirectResponse
    {
        if (!$form->isEditable()) {
            return back()->with('error', __('evaluation_items.messages.form_locked'));
        }

        return null;
    }
}
