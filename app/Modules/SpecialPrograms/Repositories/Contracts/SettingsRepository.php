<?php

namespace App\Modules\SpecialPrograms\Repositories\Contracts;

interface SettingsRepository
{
    /** 5 × [key,name,min,max,color,icon], resolved school → global → default. */
    public function bandsFor(?int $schoolId, string $program): array;

    /** @return int[] */
    public function gradeLevels(string $program): array;

    public function weeksCount(?int $schoolId): int;

    public function saveBands(?int $schoolId, string $program, array $bands, int $userId): void;

    public function setAllowSchoolEdit(int $schoolId, string $program, bool $allow, int $userId): void;

    public function canSchoolEdit(int $schoolId, string $program): bool;

    public function saveGradeLevels(string $program, array $levels, int $userId): void;

    public function saveWeeksCount(?int $schoolId, int $weeks, int $userId): void;
}
