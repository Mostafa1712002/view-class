<?php

namespace App\Modules\RawaniHub\Repositories;

use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentSubscriptionRepository implements SubscriptionRepository
{
    public function find(int $id): ?HubSubscription
    {
        return HubSubscription::find($id);
    }

    public function findForUpdate(int $id): ?HubSubscription
    {
        return HubSubscription::whereKey($id)->lockForUpdate()->first();
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return HubSubscription::with(['user', 'buyer', 'package'])
            ->when($filters['package_id'] ?? null, fn ($q, $v) => $q->where('package_id', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $attributes): HubSubscription
    {
        return HubSubscription::create($attributes);
    }

    public function update(HubSubscription $subscription, array $attributes): HubSubscription
    {
        $subscription->update($attributes);

        return $subscription;
    }

    public function activeForUser(int $userId): Collection
    {
        return HubSubscription::where('user_id', $userId)->where('status', 'active')->orderBy('starts_at')->get();
    }

    public function latestEndFor(int $userId, int $platformId, string $productCode): ?CarbonInterface
    {
        // ponytail: filters the json snapshot in PHP; a user has a handful of active rows.
        return $this->activeForUser($userId)
            ->filter(fn (HubSubscription $s) => collect($s->items)->contains(
                fn ($i) => (int) $i['platform_id'] === $platformId && $i['product_code'] === $productCode))
            ->max('ends_at');
    }

    public function expireDue(CarbonInterface $now): Collection
    {
        $due = HubSubscription::where('status', 'active')->where('ends_at', '<=', $now)->get();
        foreach ($due as $subscription) {
            $subscription->update(['status' => 'expired']);
        }

        return $due;
    }
}
