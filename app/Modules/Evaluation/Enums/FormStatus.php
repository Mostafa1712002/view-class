<?php

namespace App\Modules\Evaluation\Enums;

enum FormStatus: string
{
    case Draft     = 'draft';
    case Ready     = 'ready';
    case Published  = 'published';
    case Closed    = 'closed';
    case Archived  = 'archived';

    public function label(): string
    {
        return __('evaluation.form_status.'.$this->value);
    }

    /** Forms that can still be edited structurally. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Ready], true);
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->label();
        }
        return $out;
    }
}
