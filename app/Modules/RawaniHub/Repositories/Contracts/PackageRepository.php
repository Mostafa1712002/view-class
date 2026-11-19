<?php

namespace App\Modules\RawaniHub\Repositories\Contracts;

use App\Modules\RawaniHub\Models\HubPackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PackageRepository
{
    public function find(int $id): ?HubPackage;

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** Active and purchasable packages, ordered by sort. */
    public function purchasable(): Collection;

    public function create(array $attributes): HubPackage;

    public function update(HubPackage $package, array $attributes): HubPackage;

    /** @param array<int, array{platform_id:int, product_code:string}> $items replaces the package items */
    public function syncItems(HubPackage $package, array $items): void;

    public function delete(HubPackage $package): void;
}
