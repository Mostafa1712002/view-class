<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\EvaluationForm;
use App\Models\EvaluationIndicator;
use App\Models\EvaluationItem;
use App\Models\User;
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

            // Responsible party: the picker stores a user id; responsible_role is
            // derived from that user's admin role so execution still distributes
            // items by role slug (see EvaluationExecutionController::filterSharedPayload).
            [$responsibleUserId, $responsibleRole] = $this->resolveResponsible($form, $data['responsible_user_id'] ?? null);

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
                'responsible_user_id'             => $responsibleUserId,
                'responsible_role'                => $responsibleRole,
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

            // Inline indicator distribution (non-Rubric only — Rubric indicators bind
            // to a level via the dedicated indicators page, which this modal can't set).
            if ($form->type !== FormType::Rubric && array_key_exists('indicators', $data)) {
                $this->syncIndicators($form, $item, $isWeighted ? $weight : null, $data['indicators'] ?? []);
            }

            return $item->refresh();
        });
    }

    /**
     * Resolve the chosen responsible account into [user_id, role_slug].
     * The picker only offers admin/supervisor accounts of the form's school
     * (plus super-admins). role_slug feeds the existing role-based execution
     * distribution; null clears both.
     *
     * @return array{0: int|null, 1: string|null}
     */
    private function resolveResponsible(EvaluationForm $form, mixed $userId): array
    {
        $userId = $userId ? (int) $userId : null;
        if (!$userId) {
            return [null, null];
        }

        $user = User::query()
            ->whereKey($userId)
            ->whereHas('roles', fn ($r) => $r->whereIn('slug', ['super-admin', 'school-admin', 'supervisor']))
            ->when($form->school_id !== null, fn ($q) => $q->where(function ($w) use ($form) {
                $w->where('users.school_id', $form->school_id)
                  ->orWhereHas('roles', fn ($r) => $r->where('slug', 'super-admin'));
            }))
            ->with('roles:id,slug')
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'responsible_user_id' => __('evaluation_items.messages.item_not_found'),
            ]);
        }

        // Prefer the most specific admin role for execution distribution.
        $slugs = $user->roles->pluck('slug')->all();
        foreach (['school-admin', 'supervisor', 'super-admin'] as $slug) {
            if (in_array($slug, $slugs, true)) {
                return [$user->id, $slug];
            }
        }

        return [$user->id, $slugs[0] ?? null];
    }

    /**
     * Full-sync the criterion's indicators from the modal rows, each carrying a
     * percentage (weight). Σ percentages must not exceed the criterion weight.
     *
     * Safe to hard-delete missing rows: store/update are gated to editable
     * (draft) forms, which have no EvaluationResponse rows yet.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    private function syncIndicators(EvaluationForm $form, EvaluationItem $item, ?float $criterionWeight, array $rows): void
    {
        // Normalise + drop blank rows (a row with no text is not an indicator).
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $clean[] = [
                'id'     => isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null,
                'text'   => $text,
                'weight' => isset($row['weight']) && $row['weight'] !== '' ? round((float) $row['weight'], 2) : null,
            ];
        }

        // Σ indicator percentages ≤ criterion weight (weighted forms only).
        if ($criterionWeight !== null) {
            $sum = round(array_sum(array_map(fn ($r) => (float) ($r['weight'] ?? 0), $clean)), 2);
            if ($sum > $criterionWeight + 0.001) {
                throw ValidationException::withMessages([
                    'indicators' => __('evaluation_items.errors.indicators_over_weight', [
                        'sum'    => rtrim(rtrim(number_format($sum, 2), '0'), '.'),
                        'weight' => rtrim(rtrim(number_format($criterionWeight, 2), '0'), '.'),
                    ]),
                ]);
            }
        }

        $keepIds = array_filter(array_map(fn ($r) => $r['id'], $clean));
        $item->indicators()->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))->delete();

        foreach ($clean as $index => $row) {
            $existing = $row['id'] ? $item->indicators()->whereKey($row['id'])->first() : null;
            if ($existing) {
                $existing->fill(['text' => $row['text'], 'weight' => $row['weight'], 'sort_order' => $index + 1])->save();
            } else {
                EvaluationIndicator::create([
                    'item_id'    => $item->id,
                    'form_id'    => $form->id,
                    'level_id'   => null,
                    'text'       => $row['text'],
                    'weight'     => $row['weight'],
                    'sort_order' => $index + 1,
                    'status'     => 'active',
                ]);
            }
        }
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
