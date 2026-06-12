<?php

namespace App\Modules\Evaluation\Services;

use App\Models\Setting;

/**
 * Phase C (#205) — Resolve and persist the eval.outcome_method setting.
 *
 * Precedence (highest → lowest):
 *   1. School-level setting  (school_id = $schoolId)
 *   2. Global setting        (school_id = NULL)
 *   3. Hard-coded default    'all_registered'
 *
 * This design is non-breaking: schools that have never configured a method
 * automatically get 'all_registered', which matches the pre-v2 assumption.
 */
class EducationalOutcomeResolver
{
    private const KEY     = 'eval.outcome_method';
    private const GROUP   = 'evaluation';
    private const DEFAULT = 'all_registered';

    /**
     * Return the effective method string for a given school.
     * Falls back through global → hard-coded default.
     */
    public function methodFor(?int $schoolId): string
    {
        if ($schoolId !== null) {
            $schoolLevel = Setting::get(self::KEY, null, $schoolId);
            if ($schoolLevel !== null) {
                return (string) $schoolLevel;
            }
        }

        $global = Setting::get(self::KEY, null, null);
        if ($global !== null) {
            return (string) $global;
        }

        return self::DEFAULT;
    }

    /**
     * Save or update the method setting for a specific school.
     */
    public function setMethod(int $schoolId, string $method): void
    {
        Setting::set(self::KEY, $method, 'string', $schoolId, self::GROUP);
    }

    /**
     * Save or update the global (system-wide) default method.
     * school_id = NULL means it applies to all schools that have no override.
     */
    public function setGlobalMethod(string $method): void
    {
        Setting::set(self::KEY, $method, 'string', null, self::GROUP);
    }
}
