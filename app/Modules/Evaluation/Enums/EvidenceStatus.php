<?php

namespace App\Modules\Evaluation\Enums;

enum EvidenceStatus: string
{
    case Uploaded        = 'uploaded';
    case PendingApproval = 'pending_approval';
    case Approved        = 'approved';
    case Rejected        = 'rejected';
    case NeedsEdit       = 'needs_edit';

    public function label(): string
    {
        return match($this) {
            self::Uploaded        => __('evaluation.evidence_status_uploaded'),
            self::PendingApproval => __('evaluation.evidence_status_pending_approval'),
            self::Approved        => __('evaluation.evidence_status_approved'),
            self::Rejected        => __('evaluation.evidence_status_rejected'),
            self::NeedsEdit       => __('evaluation.evidence_status_needs_edit'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Uploaded        => 'secondary',
            self::PendingApproval => 'warning',
            self::Approved        => 'success',
            self::Rejected        => 'danger',
            self::NeedsEdit       => 'info',
        };
    }

    /** True when this status means the evidence counts toward scoring. */
    public function countsForScoring(): bool
    {
        return $this === self::Approved;
    }
}
