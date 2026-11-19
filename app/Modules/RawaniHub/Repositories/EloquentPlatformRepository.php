<?php

namespace App\Modules\RawaniHub\Repositories;

use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use Illuminate\Support\Collection;

class EloquentPlatformRepository implements PlatformRepository
{
    public function all(): Collection
    {
        return HubPlatform::orderBy('id')->get();
    }

    public function enabled(): Collection
    {
        return HubPlatform::where('is_enabled', true)->orderBy('id')->get();
    }

    public function find(int $id): ?HubPlatform
    {
        return HubPlatform::find($id);
    }

    public function findBySlug(string $slug): ?HubPlatform
    {
        return HubPlatform::where('slug', $slug)->first();
    }

    public function create(array $attributes): HubPlatform
    {
        return HubPlatform::create($attributes);
    }

    public function update(HubPlatform $platform, array $attributes): HubPlatform
    {
        $platform->update($attributes);

        return $platform;
    }
}
