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
        return array_column($this->detailedProblems($form), 'message');
    }

    /**
     * Same problems as {@see problems()}, but each carries the route+params of the
     * page where an admin can go fix it — so the publish screen can render them as
     * clickable links (card #338).
     *
     * @return array<int, array{message: string, route: string, params: array}>
     */
    public function detailedProblems(EvaluationForm $form): array
    {
        $problems = [];
        $itemsPage      = ['admin.evaluations.items.index', ['form' => $form->id]];
        $targetsPage    = ['admin.evaluations.targets.index', ['form' => $form->id]];
        $evaluatorsPage = ['admin.evaluations.evaluators.index', ['form' => $form->id]];
        $editPage       = ['admin.evaluations.edit', ['id' => $form->id]];

        if (trim((string) $form->title) === '') {
            $problems[] = $this->problem('no_title', $editPage);
        }

        $items = $form->items()->where('status', 'active')->get();
        if ($items->isEmpty()) {
            $problems[] = $this->problem('no_items', $itemsPage);
        }

        // Percentage forms have no indicators by design (one direct 0–100 value per item).
        if ($form->type !== FormType::Percentage
            && $form->indicators()->where('status', 'active')->count() === 0) {
            $problems[] = $this->problem('no_indicators', $itemsPage);
        }

        // Weighted types must sum to 100% (rubric, rating scale, percentage).
        if (in_array($form->type, [FormType::Rubric, FormType::RatingScale, FormType::Percentage], true) && $items->isNotEmpty()) {
            $sum = (float) $items->sum('weight');
            if (abs($sum - 100.0) > 0.01) {
                $problems[] = $this->problem('weights_not_100', $itemsPage, ['sum' => rtrim(rtrim(number_format($sum, 2), '0'), '.')]);
            }
            // A required item must not carry weight 0.
            if ($items->where('is_required', true)->where('weight', 0)->isNotEmpty()) {
                $problems[] = $this->problem('required_item_zero_weight', $itemsPage);
            }
        }

        // Rubric needs levels + indicators distributed across them.
        if ($form->type === FormType::Rubric && $form->levels()->count() === 0) {
            $problems[] = $this->problem('rubric_no_levels', $editPage);
        }

        if ($form->targets()->count() === 0) {
            $problems[] = $this->problem('no_targets', $targetsPage);
        }

        if ($form->assignments()->count() === 0) {
            $problems[] = $this->problem('no_evaluators', $evaluatorsPage);
        }

        if ($form->start_date && $form->close_date && $form->close_date->lt($form->start_date)) {
            $problems[] = $this->problem('close_before_start', $editPage);
        }

        return $problems;
    }

    public function isPublishable(EvaluationForm $form): bool
    {
        return $this->problems($form) === [];
    }

    /** @param array{0: string, 1: array} $target [route name, route params] */
    private function problem(string $key, array $target, array $replace = []): array
    {
        return [
            'message' => __("evaluation.checks.{$key}", $replace),
            'route'   => $target[0],
            'params'  => $target[1],
        ];
    }
}
