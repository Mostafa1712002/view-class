<?php

namespace App\Modules\RawaniHub\Repositories;

use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentPlatformAccountRepository implements PlatformAccountRepository
{
    public function find(int $id): ?HubPlatformAccount
    {
        return HubPlatformAccount::with('platform')->find($id);
    }

    public function forUser(int $userId): Collection
    {
        return HubPlatformAccount::with('platform')->where('user_id', $userId)->orderBy('id')->get();
    }

    public function findForUser(int $platformId, string $role, int $userId): ?HubPlatformAccount
    {
        return HubPlatformAccount::where(['platform_id' => $platformId, 'role' => $role, 'user_id' => $userId])->first();
    }

    public function findByExternal(int $platformId, string $role, string $externalId): ?HubPlatformAccount
    {
        return HubPlatformAccount::where(['platform_id' => $platformId, 'role' => $role, 'external_id' => $externalId])->first();
    }

    public function firstOrCreateForUser(int $platformId, string $role, int $userId, array $attributes = []): HubPlatformAccount
    {
        return HubPlatformAccount::firstOrCreate(
            ['platform_id' => $platformId, 'role' => $role, 'user_id' => $userId],
            $attributes + ['status' => 'pending', 'managed' => true],
        );
    }

    public function update(HubPlatformAccount $account, array $attributes): HubPlatformAccount
    {
        $account->update($attributes);

        return $account;
    }

    public function markDirty(array $accountIds): void
    {
        HubPlatformAccount::whereIn('id', $accountIds)->update(['needs_sync' => true]);
    }

    public function dirtyForSync(CarbonInterface $olderThan): Collection
    {
        return HubPlatformAccount::where('needs_sync', true)
            ->whereIn('status', ['pending', 'active', 'disabled'])
            ->where('updated_at', '<', $olderThan) // updated_at = last attempt (the job writes attempts/status each try)
            ->get();
    }

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return HubPlatformAccount::with(['platform', 'user'])
            ->when($filters['platform_id'] ?? null, fn ($q, $v) => $q->where('platform_id', $v))
            ->when($filters['role'] ?? null, fn ($q, $v) => $q->where('role', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(isset($filters['linked']) && $filters['linked'] !== '', fn ($q) => $filters['linked']
                ? $q->whereNotNull('user_id') : $q->whereNull('user_id'))
            ->when($filters['q'] ?? null, function ($q, $v) {
                $like = '%'.$v.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('login', 'like', $like)
                    ->orWhere('national_id', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
