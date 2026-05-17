<?php

namespace App\Modules\Lessons\Repositories\Contracts;

use App\Models\SchedulePeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LessonRepository
{
    /**
     * Paginated flat list of schedule_periods (lessons) with filters.
     */
    public function paginate(
        ?int $schoolId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator;

    public function find(int $id, ?int $schoolId): ?SchedulePeriod;
}
