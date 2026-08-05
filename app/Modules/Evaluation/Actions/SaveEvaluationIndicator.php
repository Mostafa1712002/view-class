<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationForm;
use App\Models\EvaluationIndicator;
use App\Models\EvaluationItem;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 5 — create or update an indicator under an item.
 *
 * For Rubric forms an indicator binds to one of the form's performance levels;
 * for Rating Scale / Checklist there is no level binding (forced null).
 */
class SaveEvaluationIndicator
{
    public function __construct(private readonly AuditTrail $audit)
    {
    }

    public function execute(EvaluationForm $form, EvaluationItem $item, ?EvaluationIndicator $indicator, array $data): EvaluationIndicator
    {
        return DB::transaction(function () use ($form, $item, $indicator, $data) {
            $levelId = $this->resolveLevel($form, $data['level_id'] ?? null);
            $weight  = isset($data['weight']) && $data['weight'] !== '' ? round((float) $data['weight'], 2) : null;

            // Σ this item's indicator percentages ≤ criterion weight (every weighted
            // type incl. rubric — checklist has no per-item weight to distribute).
            if ($weight !== null && $form->type !== FormType::Checklist) {
                $this->guardIndicatorWeight($item, $indicator, $weight);
            }

            $payload = [
                'text'              => $data['text'],
                'description'       => $data['description'] ?? null,
                'level_id'          => $levelId,
                'weight'            => $weight,
                'is_required'       => (bool) ($data['is_required'] ?? false),
                'needs_note'        => (bool) ($data['needs_note'] ?? false),
                'needs_evidence'    => (bool) ($data['needs_evidence'] ?? false),
                'evidence_required' => (bool) ($data['evidence_required'] ?? false),
                'status'            => ($data['status'] ?? 'active') === 'disabled' ? 'disabled' : 'active',
            ];

            if ($indicator === null) {
                $payload['item_id']    = $item->id;
                $payload['form_id']    = $form->id;
                $payload['sort_order'] = (int) ($item->indicators()->max('sort_order') ?? 0) + 1;
                $indicator = EvaluationIndicator::create($payload);
                $this->audit->created($indicator, "إضافة مؤشر: {$indicator->text}");
            } else {
                $old = $indicator->toArray();
                $indicator->fill($payload)->save();
                $this->audit->updated($indicator, "تعديل مؤشر: {$indicator->text}", $old);
            }

            return $indicator->refresh();
        });
    }

    /** Sum of OTHER active indicators' weights must leave room for this one (≤ criterion weight). */
    private function guardIndicatorWeight(EvaluationItem $item, ?EvaluationIndicator $indicator, float $weight): void
    {
        $others = (float) $item->indicators()
            ->where('status', 'active')
            ->when($indicator, fn ($q) => $q->whereKeyNot($indicator->id))
            ->sum('weight');

        $cap = round((float) $item->weight, 2);
        if (round($others + $weight, 2) > $cap + 0.001) {
            throw ValidationException::withMessages([
                'weight' => __('evaluation_items.errors.indicators_over_weight', [
                    'sum'    => rtrim(rtrim(number_format($others + $weight, 2), '0'), '.'),
                    'weight' => rtrim(rtrim(number_format($cap, 2), '0'), '.'),
                ]),
            ]);
        }
    }

    /** Rubric → must pick a valid level of this form; otherwise null. */
    private function resolveLevel(EvaluationForm $form, mixed $levelId): ?int
    {
        if ($form->type !== FormType::Rubric) {
            return null;
        }

        $levelId = $levelId ? (int) $levelId : null;
        if (!$levelId) {
            throw ValidationException::withMessages([
                'level_id' => __('evaluation_items.errors.level_required_rubric'),
            ]);
        }
        if (!$form->levels()->whereKey($levelId)->exists()) {
            throw ValidationException::withMessages([
                'level_id' => __('evaluation_items.errors.level_invalid'),
            ]);
        }

        return $levelId;
    }
}
