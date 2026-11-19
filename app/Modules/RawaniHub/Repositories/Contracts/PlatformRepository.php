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
}
