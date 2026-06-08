<?php

namespace App\Modules\Evaluation\Enums;

enum EvaluationStatus: string
{
    case Draft           = 'draft';
    case Completed       = 'completed';
    case PendingApproval = 'pending_approval';
    case Approved        = 'approved';
    case Rejected        = 'rejected';
    case NeedsReview     = 'needs_review';
    case Locked          = 'locked';

    public function label(): string
    {
        return __('evaluation.eval_status.'.$this->value);
    }

    /** Locked from further editing by the evaluator. */
    public function isLocked(): bool
    {
        return in_array($this, [self::Approved, self::Locked], true);
    }

    /** Counts toward job-performance aggregation. */
    public function countsForJobPerformance(): bool
    {
        return in_array($this, [self::Completed, self::Approved], true);
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
