<?php

namespace App\Modules\Evaluation\Enums;

enum EvidenceSource: string
{
    case Manual       = 'manual';
    case System       = 'system';
    case AutoPlatform = 'auto_platform';

    public function label(): string
    {
        return match($this) {
            self::Manual       => __('evaluation.evidence_source_manual'),
            self::System       => __('evaluation.evidence_source_system'),
            self::AutoPlatform => __('evaluation.evidence_source_auto_platform'),
        };
    }
}
