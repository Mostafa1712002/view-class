<?php

namespace App\Modules\SchoolBranches\Repositories\Contracts;

use App\Modules\SchoolBranches\Models\SchoolBranch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SchoolBranchRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function listActive(): Collection;

    public function find(int $id): ?SchoolBranch;

    public function create(array $data): SchoolBranch;

    public function update(SchoolBranch $branch, array $data): SchoolBranch;

    public function delete(SchoolBranch $branch): bool;

    public function countSchools(SchoolBranch $branch): int;
}
