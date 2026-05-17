<?php

namespace App\Modules\Libraries\Repositories\Contracts;

use App\Models\Library;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LibraryRepository
{
    public function paginatePrivate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator;

    public function findScoped(int $id, ?int $schoolId): ?Library;

    public function create(array $payload): Library;

    public function update(Library $library, array $payload): Library;

    public function delete(Library $library): void;

    public function syncAudiences(Library $library, array $audiences): void;
}
