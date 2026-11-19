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

    /**
     * Filters: platform_id, role, status, linked (bool), q. q is routed so identifiers hit their indexes:
     * digits → national_id / phone exact, an email → email exact, else name contains or login exact.
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * Upserts one GET /people page into the mirror columns (design §3.1); never touches provisioning fields.
     * Returns the ids of linked rows that came back from `missing` (now pending + needs_sync).
     *
     * @param  array<int,mixed>  $items
     * @return array<int,int>
     */
    public function mirrorPage(int $platformId, array $items, CarbonInterface $seenAt): array;

    /** Marks rows of the platform not seen since $startedAt as `missing`. An empty walk sweeps nothing. */
    public function sweepMissing(int $platformId, CarbonInterface $startedAt): int;

    /** The account's parent (student row) or children (parent row) on the same platform, from the mirror. */
    public function relatives(HubPlatformAccount $account): Collection;

    /** Unlinked mirror rows of (platform, role) whose identifier equals one of the given values, keyed by column. */
    public function unlinkedByIdentifiers(int $platformId, string $role, array $identifiers): Collection;
}
