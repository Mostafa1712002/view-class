<?php

namespace App\Modules\SpecialPrograms\Repositories;

use App\Modules\SpecialPrograms\Models\SpSetting;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\PerformanceLevels;
use App\Modules\SpecialPrograms\Support\Programs;

/** Reads never insert rows (fixes «الأول» SM §9.6). */
final class EloquentSettingsRepository implements SettingsRepository
{
    private const DEFAULT_WEEKS = 19;
    private const WEEKS_PROGRAM = 'tahsili';

    private function row(?int $schoolId, string $program): ?SpSetting
    {
        return SpSetting::where('school_id', $schoolId)->where('program', $program)->first();
    }

    private function upsert(?int $schoolId, string $program, array $values, int $userId): void
    {
        SpSetting::updateOrCreate(['school_id' => $schoolId, 'program' => $program], $values + ['updated_by' => $userId]);
    }

    public function bandsFor(?int $schoolId, string $program): array
    {
        $bands = ($schoolId !== null ? $this->row($schoolId, $program)?->level_bands : null)
            ?: $this->row(null, $program)?->level_bands;

        return $bands ? PerformanceLevels::hydrate($bands) : PerformanceLevels::defaultBands($program);
    }

    public function gradeLevels(string $program): array
    {
        return array_map('intval', $this->row(null, $program)?->grade_levels ?: Programs::defaultGradeLevels($program));
    }

    public function weeksCount(?int $schoolId): int
    {
        return (int) (($schoolId !== null ? $this->row($schoolId, self::WEEKS_PROGRAM)?->weeks_count : null)
            ?: $this->row(null, self::WEEKS_PROGRAM)?->weeks_count
            ?: self::DEFAULT_WEEKS);
    }

    public function saveBands(?int $schoolId, string $program, array $bands, int $userId): void
    {
        $slim = array_map(fn ($b) => ['key' => $b['key'], 'min' => (int) $b['min'], 'max' => (int) $b['max']], array_values($bands));
        $this->upsert($schoolId, $program, ['level_bands' => $slim], $userId);
    }

    public function setAllowSchoolEdit(int $schoolId, string $program, bool $allow, int $userId): void
    {
        $this->upsert($schoolId, $program, ['allow_school_edit' => $allow], $userId);
    }

    public function canSchoolEdit(int $schoolId, string $program): bool
    {
        return (bool) $this->row($schoolId, $program)?->allow_school_edit;
    }

    public function saveGradeLevels(string $program, array $levels, int $userId): void
    {
        $this->upsert(null, $program, ['grade_levels' => array_values(array_unique(array_map('intval', $levels)))], $userId);
    }

    public function saveWeeksCount(?int $schoolId, int $weeks, int $userId): void
    {
        $this->upsert($schoolId, self::WEEKS_PROGRAM, ['weeks_count' => $weeks], $userId);
    }
}
