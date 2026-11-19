<?php

namespace App\Modules\RawaniHub\Repositories\Contracts;

use App\Modules\RawaniHub\Models\HubPlatform;
use Illuminate\Support\Collection;

interface PlatformRepository
{
    public function all(): Collection;

    public function enabled(): Collection;

    public function find(int $id): ?HubPlatform;

    public function findBySlug(string $slug): ?HubPlatform;

    public function create(array $attributes): HubPlatform;

    public function update(HubPlatform $platform, array $attributes): HubPlatform;

    /**
     * Compare-and-set on the mirror walk: moves run $run from cursor $from to $next ($next null = walk finished).
     * False when another copy of the job already moved it, or the run is no longer current.
     */
    public function advanceMirror(int $platformId, string $run, ?string $from, ?string $next): bool;
}
