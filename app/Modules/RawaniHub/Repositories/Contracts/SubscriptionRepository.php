<?php

namespace App\Modules\RawaniHub\Repositories\Contracts;

use App\Modules\RawaniHub\Models\HubSubscription;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SubscriptionRepository
{
    public function find(int $id): ?HubSubscription;

    public function findForUpdate(int $id): ?HubSubscription;

    /** Filters: package_id, status, source, from, to. */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function create(array $attributes): HubSubscription;

    public function update(HubSubscription $subscription, array $attributes): HubSubscription;

    /** Active subscriptions of a beneficiary (input of the entitlement windows). */
    public function activeForUser(int $userId): Collection;

    /** Latest ends_at among the user's active subscriptions that contain the item, or null (for stacking). */
    public function latestEndFor(int $userId, int $platformId, string $productCode): ?CarbonInterface;

    /** Marks active subscriptions with ends_at <= $now as expired; returns the affected subscriptions. */
    public function expireDue(CarbonInterface $now): Collection;
}
