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

    public function advanceMirror(int $platformId, string $run, ?string $from, ?string $next): bool
    {
        $values = $next === null
            ? ['mirror_cursor' => null, 'mirror_started_at' => null, 'mirror_full' => false, 'mirror_finished_at' => now()]
            : ['mirror_cursor' => $next];

        return HubPlatform::whereKey($platformId)->where('mirror_started_at', $run)
            ->where(fn ($q) => $from === null ? $q->whereNull('mirror_cursor') : $q->where('mirror_cursor', $from))
            ->toBase()->update($values) === 1;
    }
}
