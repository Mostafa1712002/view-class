<?php

namespace App\Modules\Evaluation\Enums;

/**
 * Phase C (#205) — Approval lifecycle of an educational outcome record.
 *
 * draft    : newly computed, not yet reviewed
 * approved : locked for editing by non-admins
 */
enum OutcomeApprovalStatus: string
{
    case Draft    = 'draft';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => __('evaluation_outcomes.approval_status.draft'),
            self::Approved => __('evaluation_outcomes.approval_status.approved'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft    => 'warning',
            self::Approved => 'success',
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
