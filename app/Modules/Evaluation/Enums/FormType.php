<?php

namespace App\Modules\Evaluation\Enums;

enum FormType: string
{
    case Rubric      = 'rubric';
    case RatingScale = 'rating_scale';
    case Checklist   = 'checklist';

    public function label(): string
    {
        return match ($this) {
            self::Rubric      => __('evaluation.types.rubric'),
            self::RatingScale => __('evaluation.types.rating_scale'),
            self::Checklist   => __('evaluation.types.checklist'),
        };
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
