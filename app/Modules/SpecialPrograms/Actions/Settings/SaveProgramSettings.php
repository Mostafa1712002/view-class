<?php

namespace App\Modules\SpecialPrograms\Actions\Settings;

use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;

final class SaveProgramSettings
{
    public function __construct(private SettingsRepository $settings) {}

    /** Global: target grades, weeks, and the schools allowed to edit their own bands. */
    public function executeGlobal(string $program, array $gradeLevels, ?int $weeks, array $allSchoolIds, array $allowedSchoolIds, int $userId): void
    {
        $this->settings->saveGradeLevels($program, $gradeLevels, $userId);
        if ($program === 'tahsili' && $weeks !== null) {
            $this->settings->saveWeeksCount(null, $weeks, $userId);
        }
        foreach ($allSchoolIds as $schoolId) {
            $allow = in_array($schoolId, $allowedSchoolIds, true);
            // Only write when something changes, so unchecking an untouched school adds no row.
            if ($allow || $this->settings->canSchoolEdit($schoolId, $program)) {
                $this->settings->setAllowSchoolEdit($schoolId, $program, $allow, $userId);
            }
        }
    }

    public function executeSchool(int $schoolId, string $program, ?int $weeks, int $userId): void
    {
        if ($program === 'tahsili' && $weeks !== null) {
            $this->settings->saveWeeksCount($schoolId, $weeks, $userId);
        }
    }
}
