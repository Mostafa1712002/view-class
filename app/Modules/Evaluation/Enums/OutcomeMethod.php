<?php

namespace App\Modules\Evaluation\Enums;

/**
 * Phase C (#205) — How to compute the final average for an educational outcome.
 *
 * all_registered : avg = Σ(scores, absent=0) / total_registered
 * attendees_only : avg = Σ(present scores)   / present_count
 */
enum OutcomeMethod: string
{
    case AllRegistered  = 'all_registered';
    case AttendeesOnly  = 'attendees_only';

    public function label(): string
    {
        return match ($this) {
            self::AllRegistered => __('evaluation_outcomes.methods.all_registered'),
            self::AttendeesOnly => __('evaluation_outcomes.methods.attendees_only'),
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
