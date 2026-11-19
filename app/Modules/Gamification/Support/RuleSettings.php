<?php

namespace App\Modules\Gamification\Support;

use App\Modules\Gamification\Repositories\Contracts\PointSettingsRepository;

/**
 * Effective settings: catalog default <- platform row <- school row (only when the platform allows overrides).
 * The platform switch is the master switch; a school can only narrow (switch off), never switch on.
 */
class RuleSettings
{
    /** @var array<string, array> memo per instance (resolve fresh per request/action) */
    private array $memo = [];

    public function __construct(private PointSettingsRepository $settings) {}

    /** @return array{points:int, period_points:?int, enabled:bool, allow_school_override:bool, options:array} */
    public function effective(string $ruleKey, int $schoolId): array
    {
        $memoKey = $schoolId.'|'.$ruleKey;
        if (isset($this->memo[$memoKey])) {
            return $this->memo[$memoKey];
        }

        $platform = $this->settings->platform($ruleKey);
        $result = [
            'points' => (int) ($platform?->points ?? 0),
            'period_points' => $platform?->period_points,
            'enabled' => (bool) ($platform?->is_enabled ?? false),
            'allow_school_override' => (bool) ($platform?->allow_school_override ?? false),
            'options' => $platform?->options ?? [],
        ];

        if ($result['allow_school_override'] && $schoolId > 0 && ($school = $this->settings->school($schoolId, $ruleKey))) {
            $result['points'] = (int) ($school->points ?? $result['points']);
            $result['period_points'] = $school->period_points ?? $result['period_points'];
            $result['enabled'] = $result['enabled'] && ($school->is_enabled ?? true);
            $result['options'] = array_replace($result['options'], $school->options ?? []);
        }

        return $this->memo[$memoKey] = $result;
    }
}
