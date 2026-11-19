<?php

namespace App\Modules\SpecialPrograms\Actions\Settings;

use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\PerformanceLevels;

final class SaveLevelBands
{
    public function __construct(private SettingsRepository $settings) {}

    /** @param array<string,array{min:mixed,max:mixed}> $bands keyed by level key; null school = global. */
    public function execute(?int $schoolId, string $program, array $bands, int $userId): void
    {
        $rows = [];
        foreach (array_keys(PerformanceLevels::LEVELS) as $key) {
            $rows[] = ['key' => $key, 'min' => (int) $bands[$key]['min'], 'max' => (int) $bands[$key]['max']];
        }
        $this->settings->saveBands($schoolId, $program, $rows, $userId);
    }
}
