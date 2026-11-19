<?php

namespace App\Modules\RawaniHub\Repositories;

use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentPackageRepository implements PackageRepository
{
    public function find(int $id): ?HubPackage
    {
        return HubPackage::with('items')->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return HubPackage::with('items')
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name_ar', 'like', '%'.$v.'%'))
            ->orderBy('sort')->orderByDesc('id')
            ->paginate($perPage);
    }

    public function purchasable(): Collection
    {
        return HubPackage::with('items')->where('is_active', true)->where('is_purchasable', true)
            ->orderBy('sort')->orderBy('id')->get();
    }

    public function create(array $attributes): HubPackage
    {
        return HubPackage::create($attributes);
    }

    public function update(HubPackage $package, array $attributes): HubPackage
    {
        $package->update($attributes);

        return $package;
    }

    public function syncItems(HubPackage $package, array $items): void
    {
        $package->items()->delete();
        foreach ($items as $item) {
            $package->items()->create([
                'platform_id' => $item['platform_id'],
                'product_code' => $item['product_code'],
            ]);
        }
    }

    public function delete(HubPackage $package): void
    {
        $package->delete();
    }
}
