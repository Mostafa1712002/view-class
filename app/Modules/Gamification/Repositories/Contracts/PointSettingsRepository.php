<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Modules\Gamification\Models\PointRuleSetting;
use Illuminate\Support\Collection;

interface PointSettingsRepository
{
    public function platform(string $ruleKey): ?PointRuleSetting;

    public function school(int $schoolId, string $ruleKey): ?PointRuleSetting;

    /** @return Collection<string, PointRuleSetting> keyed by rule_key; schoolId 0 = platform rows */
    public function allFor(int $schoolId): Collection;

    public function save(int $schoolId, string $ruleKey, array $attributes, ?int $updatedBy): PointRuleSetting;
}
