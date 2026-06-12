<?php

namespace App\Modules\Evaluation\Enums;

enum ItemType: string
{
    case Manual       = 'manual';
    case Auto         = 'auto';
    case EvidenceOnly = 'evidence_only';
    case Mixed        = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Manual       => __('evaluation_items.item_types.manual'),
            self::Auto         => __('evaluation_items.item_types.auto'),
            self::EvidenceOnly => __('evaluation_items.item_types.evidence_only'),
            self::Mixed        => __('evaluation_items.item_types.mixed'),
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
