<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\EvaluationItem;
use App\Models\EvaluationResponse;
use App\Modules\Evaluation\Actions\SaveEvaluationItem;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationItemController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly EvaluationFormRepository $forms,
        private readonly SaveEvaluationItem $saveItem,
        private readonly AuditTrail $audit,
    ) {
    }

    /** Task 4 — list a form's items with weight totals. */
    public function index(int $form): View|RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }

        $items = $evForm->items()->withCount('indicators')->orderBy('sort_order')->get();

        return view('admin.evaluation.items.index', [
            'form'            => $evForm,
            'items'           => $items,
            'isWeighted'      => $evForm->type !== FormType::Checklist,
            'weightTotal'     => round((float) $items->where('status', 'active')->sum('weight'), 2),
        ]);
    }

    public function store(Request $request, int $form): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }
        if ($lock = $this->guardEditable($evForm)) {
            return $lock;
        }

        $this->saveItem->execute($evForm, null, $this->validatedData($request));

        return redirect()->route('admin.evaluations.items.index', $evForm->id)
            ->with('status', __('evaluation_items.messages.item_created'));
    }

    public function update(Request $request, int $form, int $item): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }
        if ($lock = $this->guardEditable($evForm)) {
            return $lock;
        }
        $model = $evForm->items()->find($item);
        if (!$model) {
            return back()->with('error', __('evaluation_items.messages.item_not_found'));
        }

        $this->saveItem->execute($evForm, $model, $this->validatedData($request));

        return redirect()->route('admin.evaluations.items.index', $evForm->id)
            ->with('status', __('evaluation_items.messages.item_updated'));
    }

    /** Toggle active/disabled. */
    public function toggle(int $form, int $item): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }
        $model = $evForm->items()->find($item);
        if (!$model) {
            return back()->with('error', __('evaluation_items.messages.item_not_found'));
        }

        $old = $model->toArray();
        $model->status = $model->status === 'active' ? 'disabled' : 'active';
        $model->save();
        $this->audit->updated($model, "تغيير حالة عنصر التقييم: {$model->name}", $old);

        $key = $model->status === 'active' ? 'item_enabled' : 'item_disabled';

        return back()->with('status', __('evaluation_items.messages.'.$key));
    }

    public function destroy(int $form, int $item): RedirectResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return $evForm;
        }
        if ($lock = $this->guardEditable($evForm)) {
            return $lock;
        }
        $model = $evForm->items()->find($item);
        if (!$model) {
            return back()->with('error', __('evaluation_items.messages.item_not_found'));
        }

        // Disable-not-delete when the item is used in a real evaluation (Task 4 rule).
        if (EvaluationResponse::where('item_id', $model->id)->exists()) {
            return back()->with('error', __('evaluation_items.messages.delete_blocked_item'));
        }

        $this->audit->deleted($model, "حذف عنصر تقييم: {$model->name}");
        $model->indicators()->delete();
        $model->delete();

        return back()->with('status', __('evaluation_items.messages.item_deleted'));
    }

    /** Persist drag-and-drop ordering. */
    public function reorder(Request $request, int $form): JsonResponse
    {
        $evForm = $this->resolveForm($form);
        if (!$evForm instanceof EvaluationForm) {
            return response()->json(['success' => false], 404);
        }
        $request->validate(['items' => 'required|array', 'items.*' => 'integer']);

        foreach ($request->input('items') as $index => $itemId) {
            $evForm->items()->whereKey((int) $itemId)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name'                            => ['required', 'string', 'max:255'],
            'description'                     => ['nullable', 'string'],
            'weight'                          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_score'                       => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'is_required'                     => ['nullable', 'boolean'],
            'needs_evidence'                  => ['nullable', 'boolean'],
            'evidence_required'               => ['nullable', 'boolean'],
            'allow_note'                      => ['nullable', 'boolean'],
            'visible_to_evaluator_only'       => ['nullable', 'boolean'],
            'visible_to_subject_after_result' => ['nullable', 'boolean'],
            'status'                          => ['nullable', 'in:active,disabled'],
        ]);
    }

    /** Tenant + existence scoped fetch; returns redirect on failure. */
    private function resolveForm(int $form): EvaluationForm|RedirectResponse
    {
        $evForm = $this->forms->findScoped($form, $this->activeSchoolId());
        if (!$evForm) {
            return redirect()->route('admin.evaluations.index')
                ->with('error', __('evaluation_items.messages.form_not_found'));
        }

        return $evForm;
    }

    /** Block structural edits on a published/closed form. */
    private function guardEditable(EvaluationForm $form): ?RedirectResponse
    {
        if (!$form->isEditable()) {
            return back()->with('error', __('evaluation_items.messages.form_locked'));
        }

        return null;
    }
}
