<?php

namespace App\Modules\Promotion\Repositories\Contracts;

use App\Models\ClassRoom;

interface EnrollmentResultRepository
{
    /**
     * Load a class only if it belongs to $schoolId (via its section). Returns
     * null when the class is missing or owned by another school — the tenant
     * boundary for the pass/fail roster.
     */
    public function findScopedClass(int $classId, int $schoolId): ?ClassRoom;

    /**
     * Enrolled students of the class with their pivot `result`, ordered by name.
     *
     * @return \Illuminate\Support\Collection<int,\App\Models\User>
     */
    public function rosterStudents(ClassRoom $class);

    /**
     * Set pivot `result` per student for a class, scoped to $schoolId. Every row
     * touched is re-checked to belong to the school, so a student outside the
     * school can never be modified even if a stray id is posted.
     *
     * @param  array<int,string>  $studentResults  studentId => pending|passed|failed
     * @return int  rows updated
     */
    public function setResults(int $classId, int $schoolId, array $studentResults): int;

    /**
     * Mark every enrollment of the class as 'passed', scoped to $schoolId.
     *
     * @return int  rows updated
     */
    public function markAllPassed(int $classId, int $schoolId): int;
}
