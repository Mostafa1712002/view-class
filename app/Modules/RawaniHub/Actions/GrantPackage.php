<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\DTOs\GrantData;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Services\SubscriptionAccounts;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Free admin grant of one package to one or more beneficiaries; renewals stack after the latest end. */
final class GrantPackage
{
    use RequiresSuperAdmin;

    public function __construct(
        private PackageRepository $packages,
        private SubscriptionRepository $subscriptions,
        private PlatformAccountRepository $accounts,
        private SubscriptionAccounts $subscriptionAccounts,
        private AccountSyncDispatcher $dispatcher,
    ) {}

    /** @return Collection<int, HubSubscription> */
    public function execute(User $actor, GrantData $data): Collection
    {
        $this->authorizeHubAdmin($actor);

        $package = $this->packages->find($data->packageId);
        $userIds = array_values(array_unique(array_map('intval', $data->userIds)));
        if (! $package || ! $package->is_active) {
            throw ValidationException::withMessages(['package_id' => 'unknown or inactive package']);
        }
        if ($userIds === [] || User::whereIn('id', $userIds)->count() !== count($userIds)) {
            throw ValidationException::withMessages(['user_ids' => 'at least one existing beneficiary is required']);
        }

        $items = $package->items->map(fn ($i) => ['platform_id' => $i->platform_id, 'product_code' => $i->product_code])->all();
        $baseStart = $data->startsAt ?? now();

        return DB::transaction(function () use ($actor, $data, $package, $userIds, $items, $baseStart) {
            $created = collect();
            $dirty = [];
            foreach ($userIds as $userId) {
                // ponytail: no per-user lock; two simultaneous grants to the same person could overlap, not stack.
                $start = $baseStart;
                foreach ($items as $item) {
                    $end = $this->subscriptions->latestEndFor($userId, $item['platform_id'], $item['product_code']);
                    if ($end && $end->greaterThan($start)) {
                        $start = $end;
                    }
                }
                $sub = $this->subscriptions->create([
                    'user_id' => $userId, 'buyer_id' => null, 'package_id' => $package->id, 'items' => $items,
                    'price' => 0, 'source' => 'grant', 'status' => 'active',
                    'starts_at' => $start, 'ends_at' => $start->copy()->addDays($package->duration_days),
                    'granted_by' => $actor->id, 'note' => $data->note,
                ]);
                $dirty = array_merge($dirty, $this->subscriptionAccounts->forBeneficiary($userId, $items, create: true));
                ActivityLog::log('hub.subscription.grant', "منح باقة «{$package->name_ar}» للمستخدم #{$userId}", $sub, null, $sub->toArray());
                $created->push($sub);
            }
            $this->accounts->markDirty($dirty);
            $this->dispatcher->afterCommit($dirty);

            return $created;
        });
    }
}
