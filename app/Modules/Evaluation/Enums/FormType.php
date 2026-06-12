<?php

namespace App\Modules\Evaluation\Enums;

enum FormType: string
{
    case Rubric      = 'rubric';
    case RatingScale = 'rating_scale';
    case Checklist   = 'checklist';
    case Percentage  = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::Rubric      => __('evaluation.types.rubric'),
            self::RatingScale => __('evaluation.types.rating_scale'),
            self::Checklist   => __('evaluation.types.checklist'),
            self::Percentage  => __('evaluation.types.percentage'),
        };
    }

    /**
     * Whether this form type uses per-item weights summing to 100 (the
     * weighted-percentage family). Checklist is coverage-based and excluded.
     */
    public function usesWeights(): bool
    {
        return $this !== self::Checklist;
    }

    /**
     * Whether the evaluator enters a direct 0–100 percentage per item rather
     * than picking levels/indicators.
     */
    public function isDirectPercentage(): bool
    {
        return $this === self::Percentage;
    }

    /** @return array<string,string> value => label */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }
        return $out;
    }
}
