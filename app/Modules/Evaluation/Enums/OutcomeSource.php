<?php

namespace App\Modules\Evaluation\Enums;

/**
 * Phase C (#205) — Origin of an educational outcome record.
 *
 * manual           : entered manually by a school admin
 * imported         : uploaded via spreadsheet/CSV
 * internal         : generated from the internal scoring engine (Phase F)
 * external_alawwal : pulled from منصة الأول (TODO Phase D)
 * external_qudrat  : pulled from أنا والقدرات   (TODO Phase D)
 */
enum OutcomeSource: string
{
    case Manual          = 'manual';
    case Imported        = 'imported';
    case Internal        = 'internal';
    case ExternalAlawwal = 'external_alawwal';
    case ExternalQudrat  = 'external_qudrat';

    public function label(): string
    {
        return match ($this) {
            self::Manual          => __('evaluation_outcomes.sources.manual'),
            self::Imported        => __('evaluation_outcomes.sources.imported'),
            self::Internal        => __('evaluation_outcomes.sources.internal'),
            self::ExternalAlawwal => __('evaluation_outcomes.sources.external_alawwal'),
            self::ExternalQudrat  => __('evaluation_outcomes.sources.external_qudrat'),
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
