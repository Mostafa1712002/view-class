<?php

namespace App\Modules\Evaluation\Enums;

enum CalcMethod: string
{
    case Manual         = 'manual';
    case AutoPlatform   = 'auto_platform';
    case AfterEvidence  = 'after_evidence';
    case External       = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Manual        => __('evaluation_items.calc_methods.manual'),
            self::AutoPlatform  => __('evaluation_items.calc_methods.auto_platform'),
            self::AfterEvidence => __('evaluation_items.calc_methods.after_evidence'),
            self::External      => __('evaluation_items.calc_methods.external'),
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
