<?php

namespace App\Modules\SpecialPrograms\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface EnrollmentRepository
{
    /** Users joined to active enrolments of the school/program. */
    public function enrolledQuery(int $schoolId, string $program): Builder;

    public function isEnrolled(int $studentId, string $program): bool;

    /** @return string[] program keys */
    public function programsOf(int $studentId): array;

    /** @param int[] $studentIds @return int number newly enrolled/restored */
    public function enroll(int $schoolId, string $program, array $studentIds, ?int $groupId, int $byUserId): int;

    public function remove(int $schoolId, string $program, int $studentId): void;
}
