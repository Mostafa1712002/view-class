<?php

namespace App\Modules\Users\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserListRepository
{
    /**
     * Paginate users matching the role this repo represents,
     * scoped to the given school, optionally filtered by search.
     */
    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator;

    /**
     * Find a user by id, scoped to school + role of this repo.
     * Returns null if mismatched.
     */
    public function findScoped(int $id, ?int $schoolId): ?\App\Models\User;
}
