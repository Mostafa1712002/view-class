<?php

namespace App\Modules\SchoolBranches\Repositories;

use App\Modules\SchoolBranches\Models\SchoolBranch;
use App\Modules\SchoolBranches\Repositories\Contracts\SchoolBranchRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentSchoolBranchRepository implements SchoolBranchRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return SchoolBranch::query()
            ->withCount('schools')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function listActive(): Collection
    {
        return SchoolBranch::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();
    }

    public function find(int $id): ?SchoolBranch
    {
        return SchoolBranch::find($id);
    }

    public function create(array $data): SchoolBranch
    {
        return SchoolBranch::create($data);
    }

    public function update(SchoolBranch $branch, array $data): SchoolBranch
    {
        $branch->update($data);
        return $branch->refresh();
    }

    public function delete(SchoolBranch $branch): bool
    {
        return (bool) $branch->delete();
    }

    public function countSchools(SchoolBranch $branch): int
    {
        return $branch->schools()->count();
    }
}
