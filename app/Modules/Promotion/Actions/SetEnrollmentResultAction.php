<?php

namespace App\Modules\Promotion\Actions;

use App\Modules\Promotion\Repositories\Contracts\EnrollmentResultRepository;

/**
 * Set (confirm) pass/fail results on a class roster. Every write is scoped to
 * the acting school; the repository re-checks tenant ownership per row.
 */
final class SetEnrollmentResultAction
{
    public function __construct(private EnrollmentResultRepository $repo) {}

    /**
     * @param  array<int|string,string>  $results  studentId => pending|passed|failed
     * @return int  enrollment rows updated
     */
    public function execute(int $classId, int $schoolId, array $results): int
    {
        return $this->repo->setResults($classId, $schoolId, $results);
    }

    public function markAllPassed(int $classId, int $schoolId): int
    {
        return $this->repo->markAllPassed($classId, $schoolId);
    }
}
