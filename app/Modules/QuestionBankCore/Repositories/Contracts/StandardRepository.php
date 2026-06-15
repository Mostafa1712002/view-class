<?php

namespace App\Modules\QuestionBankCore\Repositories\Contracts;

use App\Modules\QuestionBankCore\Models\Standard;
use Illuminate\Support\Collection;

interface StandardRepository
{
    /** Active standards, optionally filtered by subject and/or domain. */
    public function listActive(?int $subjectId = null, ?int $domainId = null): Collection;

    public function find(int $id): ?Standard;

    public function create(array $data): Standard;

    public function update(Standard $standard, array $data): Standard;

    public function delete(Standard $standard): bool;
}
