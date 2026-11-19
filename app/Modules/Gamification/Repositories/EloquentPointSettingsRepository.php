<?php

namespace App\Modules\Gamification\Repositories;

use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointSettingsRepository;
use Illuminate\Support\Collection;

class EloquentPointSettingsRepository implements PointSettingsRepository
{
    public function platform(string $ruleKey): ?PointRuleSetting
    {
        return $this->school(PointRuleSetting::PLATFORM, $ruleKey);
    }

    public function school(int $schoolId, string $ruleKey): ?PointRuleSetting
    {
        return PointRuleSetting::where('school_id', $schoolId)->where('rule_key', $ruleKey)->first();
    }

    public function allFor(int $schoolId): Collection
    {
        return PointRuleSetting::where('school_id', $schoolId)->get()->keyBy('rule_key');
    }

    public function save(int $schoolId, string $ruleKey, array $attributes, ?int $updatedBy): PointRuleSetting
    {
        return PointRuleSetting::updateOrCreate(
            ['school_id' => $schoolId, 'rule_key' => $ruleKey],
            $attributes + ['updated_by' => $updatedBy],
        );
    }
}
