<?php

namespace App\Modules\QuestionBankCore\Repositories\Contracts;

use App\Modules\QuestionBankCore\Models\Standard;
use Illuminate\Support\Collection;

interface StandardRepository
{
    /** Active standards, optionally filtered by subject and/or domain. */
    public function listActive(?int $subjectId = null, ?int $domainId = null): Collection;

    /**
     * Paginated admin list (incl. inactive) with optional filters (q, subject_id, status).
     *
     * @param  array<string,mixed>  $filters
     */
    public function paginateForAdmin(array $filters, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function find(int $id): ?Standard;

    public function create(array $data): Standard;

    public function update(Standard $standard, array $data): Standard;

    public function delete(Standard $standard): bool;
}
