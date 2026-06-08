<?php

namespace App\Modules\Evaluation\Enums;

enum UsageDomain: string
{
    case Teacher           = 'teacher';
    case Admin             = 'admin';
    case ClassVisit        = 'class_visit';
    case Student           = 'student';
    case Parent            = 'parent';
    case SchoolEnvironment = 'school_environment';
    case General           = 'general';
    case JobPerformance    = 'job_performance';

    public function label(): string
    {
        return __('evaluation.domains.'.$this->value);
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
