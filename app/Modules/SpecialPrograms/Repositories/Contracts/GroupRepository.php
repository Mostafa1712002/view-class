<?php

namespace App\Modules\SpecialPrograms\Repositories\Contracts;

use Illuminate\Support\Collection;

interface GroupRepository
{
    public function forSchool(int $schoolId, string $program, ?int $teacherId = null): Collection;

    /** @return int[] */
    public function groupIdsLedBy(int $teacherId, string $program): array;

    /** @return int[]|null null = all subjects; [] = not assigned */
    public function teacherSubjectIds(int $schoolId, int $teacherId, string $program): ?array;
}
