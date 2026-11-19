<?php

namespace App\Modules\RawaniHub\Repositories\Contracts;

use App\Modules\RawaniHub\Models\HubPlatformAccount;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PlatformAccountRepository
{
    public function find(int $id): ?HubPlatformAccount;

    public function forUser(int $userId): Collection;

    public function findForUser(int $platformId, string $role, int $userId): ?HubPlatformAccount;

    public function findByExternal(int $platformId, string $role, string $externalId): ?HubPlatformAccount;

    /** Existing row for (platform, role, user) or a new pending one. Safe against the unique key. */
    public function firstOrCreateForUser(int $platformId, string $role, int $userId, array $attributes = []): HubPlatformAccount;

    public function update(HubPlatformAccount $account, array $attributes): HubPlatformAccount;

    /** Sets needs_sync=1 on the given account ids. */
    public function markDirty(array $accountIds): void;

    /** needs_sync rows in pending|active|disabled whose last touch (updated_at) is older than $olderThan. */
    public function dirtyForSync(CarbonInterface $olderThan): Collection;

    /** Filters: platform_id, role, status, linked (bool), q (name/login/national_id/email/phone). */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;
}
