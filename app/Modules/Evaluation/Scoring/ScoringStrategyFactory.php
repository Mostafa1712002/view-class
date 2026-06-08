<?php

namespace App\Modules\Evaluation\Scoring;

use App\Modules\Evaluation\Enums\FormType;
use InvalidArgumentException;

/**
 * Resolves the right {@see ScoringStrategy} for a form type. Adding a new form
 * type means adding a case here and a scorer class — existing scorers are
 * untouched (open/closed).
 */
final class ScoringStrategyFactory
{
    /**
     * @param FormType|string $type A FormType enum or its string value
     *                              (rubric|rating_scale|checklist).
     *
     * @throws InvalidArgumentException for an unknown type.
     */
    public function for(FormType|string $type): ScoringStrategy
    {
        $formType = $type instanceof FormType ? $type : FormType::tryFrom($type);

        return match ($formType) {
            FormType::Rubric      => new RubricScorer(),
            FormType::RatingScale => new RatingScaleScorer(),
            FormType::Checklist   => new ChecklistScorer(),
            default               => throw new InvalidArgumentException(
                'No scoring strategy for evaluation form type: '
                . (is_string($type) ? $type : $type->value)
            ),
        };
    }
}
