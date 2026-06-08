<?php

namespace App\Modules\Evaluation\Enums;

enum VisitStatus: string
{
    case Scheduled       = 'scheduled';
    case Secret          = 'secret';
    case TeacherNotified = 'teacher_notified';
    case InProgress      = 'in_progress';
    case Completed       = 'completed';
    case Postponed       = 'postponed';
    case Cancelled       = 'cancelled';
    case Missed          = 'missed';

    public function label(): string
    {
        return __('evaluation.visit_status.'.$this->value);
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
