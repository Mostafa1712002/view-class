<?php

namespace App\Modules\Users\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TeamRepository
{
    /** @param array{type?:?string,q?:?string,job_title_id?:?int,status?:?string} $filters */
    public function paginate(?int $schoolId, array $filters, int $perPage = 25): LengthAwarePaginator;

    /** @return array{total:int,teachers:int,admins:int,active:int} */
    public function stats(?int $schoolId): array;
}
