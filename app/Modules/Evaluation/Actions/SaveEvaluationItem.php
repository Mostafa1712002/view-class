<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationForm;
use App\Models\EvaluationItem;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 4 — create or update an evaluation item under a form.
 *
 * Enforces the weighted-type rules: the running total of active item weights
 * must not exceed 100% (the per-item slice), and a required item may not carry
 * a zero weight. Checklist forms ignore weights entirely.
 */
class SaveEvaluationItem
{
    public function __construct(private readonly AuditTrail $audit)
    {
    }

    public function execute(EvaluationForm $form, ?EvaluationItem $item, array $data): EvaluationItem
    {
        return DB::transaction(function () use ($form, $item, $data) {
            $isWeighted = $form->type !== FormType::Checklist;
            $weight     = $isWeighted ? round((float) ($data['weight'] ?? 0), 2) : 0.0;
            $isRequired = (bool) ($data['is_required'] ?? false);

            if ($isWeighted) {
                $this->guardWeights($form, $item, $weight, $isRequired);
            }

            $payload = [
                'name'                            => $data['name'],
                'description'                     => $data['description'] ?? null,
                'weight'                          => $weight,
                'max_score'                       => round((float) ($data['max_score'] ?? 100), 2),
                'is_required'                     => $isRequired,
                'needs_evidence'                  => (bool) ($data['needs_evidence'] ?? false),
                'evidence_required'               => (bool) ($data['evidence_required'] ?? false),
                'allow_note'                      => (bool) ($data['allow_note'] ?? false),
                'visible_to_evaluator_only'       => (bool) ($data['visible_to_evaluator_only'] ?? false),
                'visible_to_subject_after_result' => (bool) ($data['visible_to_subject_after_result'] ?? false),
                'status'                          => ($data['status'] ?? 'active') === 'disabled' ? 'disabled' : 'active',
                // Phase A (v2) advanced item config — all optional, fall back to DB defaults
                'responsible_role'                => ($data['responsible_role'] ?? null) ?: null,
                'item_type'                       => $data['item_type'] ?? 'manual',
                'calc_method'                     => $data['calc_method'] ?? 'manual',
                'evidence_needs_approval'         => (bool) ($data['evidence_needs_approval'] ?? false),
                'editable_after_review'           => (bool) ($data['editable_after_review'] ?? false),
                'editable_after_approval'         => (bool) ($data['editable_after_approval'] ?? false),
                'min_percentage'                  => isset($data['min_percentage']) && $data['min_percentage'] !== ''
                                                        ? round((float) $data['min_percentage'], 2)
                                                        : null,
                'internal_notes'                  => $data['internal_notes'] ?? null,
            ];

            if ($item === null) {
                $payload['form_id']    = $form->id;
                $payload['sort_order'] = (int) ($form->items()->max('sort_order') ?? 0) + 1;
                $item = EvaluationItem::create($payload);
                $this->audit->created($item, "إضافة عنصر تقييم: {$item->name}");
            } else {
                $old = $item->toArray();
                $item->fill($payload)->save();
                $this->audit->updated($item, "تعديل عنصر تقييم: {$item->name}", $old);
            }

            return $item->refresh();
        });
    }

    /** Sum of OTHER active items' weights must leave room for this one (≤100%). */
    private function guardWeights(EvaluationForm $form, ?EvaluationItem $item, float $weight, bool $isRequired): void
    {
        if ($isRequired && $weight <= 0) {
            throw ValidationException::withMessages([
                'weight' => __('evaluation_items.errors.required_zero_weight'),
            ]);
        }

        $others = $form->items()
            ->where('status', 'active')
            ->when($item, fn ($q) => $q->whereKeyNot($item->id))
            ->sum('weight');

        $remaining = round(100 - (float) $others, 2);

        if ($weight > $remaining + 0.001) {
            throw ValidationException::withMessages([
                'weight' => __('evaluation_items.errors.weight_over_100', ['remaining' => max(0, $remaining)]),
            ]);
        }
    }
}
