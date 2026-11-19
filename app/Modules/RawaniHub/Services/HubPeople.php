<?php

namespace App\Modules\RawaniHub\Services;

use App\Models\User;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Read-only lookups for the admin screens (grant form and person page). */
class HubPeople
{
    /** @param array<int,string> $nationalIds @return Collection<int,User> */
    public function byNationalIds(array $nationalIds): Collection
    {
        return User::whereIn('national_id', array_filter($nationalIds))->get();
    }

    /** @return Collection<int,User> */
    public function childrenOfNationalId(string $nationalId): Collection
    {
        $parent = User::where('national_id', $nationalId)->first();

        return $parent ? $parent->children()->get() : collect();
    }

    public function find(int $userId): User
    {
        return User::with(['children', 'parents'])->findOrFail($userId);
    }

    /** «أشخاص رواني» tab: people with any hub account or subscription. Filters: platform_id, q (digits → national_id/phone, email, else name). */
    public function directory(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $platform = $filters['platform_id'] ?? null;

        return User::query()
            ->where(fn ($w) => $w->whereExists(fn ($q) => $q->from('hub_platform_accounts')->whereColumn('hub_platform_accounts.user_id', 'users.id')
                ->when($platform, fn ($q, $p) => $q->where('platform_id', $p)))
                ->when(! $platform, fn ($w) => $w->orWhereExists(fn ($q) => $q->from('hub_subscriptions')->whereColumn('hub_subscriptions.user_id', 'users.id'))))
            ->when(trim((string) ($filters['q'] ?? '')), function ($q, $v) {
                $digits = preg_replace('/[\s\-+]/', '', $v);
                $q->where(fn ($w) => ctype_digit($digits)
                    ? $w->where('national_id', $digits)->orWhere('phone', $v)->orWhere('phone', $digits)
                    : (str_contains($v, '@') ? $w->where('email', mb_strtolower($v))
                        : $w->where('name', 'like', '%'.addcslashes($v, '%_\\').'%')->orWhere('username', $v)));
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /** @param array<int,int> $userIds @return Collection<int,Collection<int,HubPlatformAccount>> keyed by user id */
    public function accountsByUser(array $userIds): Collection
    {
        return HubPlatformAccount::with('platform')->whereIn('user_id', $userIds)->orderBy('platform_id')->get()->groupBy('user_id');
    }
}
