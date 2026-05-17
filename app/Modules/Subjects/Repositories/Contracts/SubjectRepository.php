<?php

namespace App\Modules\Subjects\Repositories\Contracts;

use App\Models\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SubjectRepository
{
    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator;

    public function findScoped(int $id, ?int $schoolId): ?Subject;

    public function create(array $payload): Subject;

    public function update(Subject $subject, array $payload): Subject;

    public function delete(Subject $subject): void;

    public function bulkSetCreditHours(?int $schoolId, array $creditHoursById): int;

    /**
     * Subjects in the picked grade level (matches the JSON-stringified value in
     * subjects.grade_levels e.g. ["1","2"]).
     *
     * @return iterable<Subject>
     */
    public function subjectsForGradeLevel(?int $schoolId, int $level): iterable;

    /**
     * Persist both `credit_hours` (weekly lessons) and `credit_hours_active`
     * (actual-approved toggle) in one pass.
     */
    public function bulkSetCreditValues(?int $schoolId, array $hoursById, array $activeById): int;

    /** @return iterable<Subject>  ViewClass platform templates (school_id NULL) */
    public function platformTemplates(): iterable;
}
