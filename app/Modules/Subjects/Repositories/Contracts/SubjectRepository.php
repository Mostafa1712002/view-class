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

    /** @return iterable<Subject>  ViewClass platform templates (school_id NULL) */
    public function platformTemplates(): iterable;
}
