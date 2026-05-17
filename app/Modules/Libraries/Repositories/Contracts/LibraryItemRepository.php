<?php

namespace App\Modules\Libraries\Repositories\Contracts;

use App\Models\LibraryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LibraryItemRepository
{
    public function paginatePublic(?int $schoolId, array $filters = [], int $perPage = 12): LengthAwarePaginator;

    public function paginateForLibrary(int $libraryId, ?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator;

    public function findScoped(int $id, ?int $schoolId): ?LibraryItem;

    public function create(array $payload): LibraryItem;

    public function update(LibraryItem $item, array $payload): LibraryItem;

    public function delete(LibraryItem $item): void;
}
