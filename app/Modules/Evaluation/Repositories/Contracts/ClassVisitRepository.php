<?php

namespace App\Modules\Evaluation\Repositories\Contracts;

use App\Models\ClassVisit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ClassVisitRepository
{
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findScoped(int $id, ?int $schoolId): ?ClassVisit;

    public function create(array $payload): ClassVisit;

    public function update(ClassVisit $visit, array $payload): ClassVisit;

    public function delete(ClassVisit $visit): void;

    /** Guard: same teacher + period + date already has a visit. */
    public function existsForSlot(int $teacherId, ?int $periodId, string $visitDate, ?int $ignoreId = null): bool;
}
