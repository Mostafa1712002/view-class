<?php

namespace App\Modules\Evaluation\Services;

use App\Models\EvaluationForm;
use App\Modules\Evaluation\Enums\FormType;

/**
 * Validates whether a form is complete enough to publish (Task 8 publish gate +
 * Task 1 "ready for publish" status). Returns a list of human-readable problems;
 * an empty list means the form is publishable.
 */
class FormCompletenessChecker
{
    /** @return string[] list of problem messages (empty = ready) */
    public function problems(EvaluationForm $form): array
    {
        $problems = [];

        if (trim((string) $form->title) === '') {
            $problems[] = __('evaluation.checks.no_title');
        }

        $items = $form->items()->where('status', 'active')->get();
        if ($items->isEmpty()) {
            $problems[] = __('evaluation.checks.no_items');
        }

        // Percentage forms have no indicators by design (one direct 0–100 value per item).
        if ($form->type !== FormType::Percentage
            && $form->indicators()->where('status', 'active')->count() === 0) {
            $problems[] = __('evaluation.checks.no_indicators');
        }

        // Weighted types must sum to 100% (rubric, rating scale, percentage).
        if (in_array($form->type, [FormType::Rubric, FormType::RatingScale, FormType::Percentage], true) && $items->isNotEmpty()) {
            $sum = (float) $items->sum('weight');
            if (abs($sum - 100.0) > 0.01) {
                $problems[] = __('evaluation.checks.weights_not_100', ['sum' => rtrim(rtrim(number_format($sum, 2), '0'), '.')]);
            }
            // A required item must not carry weight 0.
            if ($items->where('is_required', true)->where('weight', 0)->isNotEmpty()) {
                $problems[] = __('evaluation.checks.required_item_zero_weight');
            }
        }

        // Rubric needs levels + indicators distributed across them.
        if ($form->type === FormType::Rubric && $form->levels()->count() === 0) {
            $problems[] = __('evaluation.checks.rubric_no_levels');
        }

        if ($form->targets()->count() === 0) {
            $problems[] = __('evaluation.checks.no_targets');
        }

        if ($form->assignments()->count() === 0) {
            $problems[] = __('evaluation.checks.no_evaluators');
        }

        if ($form->start_date && $form->close_date && $form->close_date->lt($form->start_date)) {
            $problems[] = __('evaluation.checks.close_before_start');
        }

        return $problems;
    }

    public function isPublishable(EvaluationForm $form): bool
    {
        return $this->problems($form) === [];
    }
}
