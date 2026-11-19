<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubSubscription;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Services\SubscriptionAccounts;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Revokes a pending/active subscription. It does not refund money (that is RecordRefund). */
final class CancelSubscription
{
    use RequiresSuperAdmin;

    public function __construct(
        private SubscriptionRepository $subscriptions,
        private PlatformAccountRepository $accounts,
        private SubscriptionAccounts $subscriptionAccounts,
        private AccountSyncDispatcher $dispatcher,
    ) {}

    public function execute(User $actor, int $subscriptionId, ?string $note = null): HubSubscription
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($actor, $subscriptionId, $note) {
            $sub = $this->subscriptions->findForUpdate($subscriptionId);
            if (! $sub) {
                throw ValidationException::withMessages(['subscription' => 'not found']);
            }
            if (! in_array($sub->status, ['pending', 'active'], true)) {
                throw ValidationException::withMessages(['subscription' => "cannot cancel a {$sub->status} subscription"]);
            }
            $old = $sub->toArray();
            $sub = $this->subscriptions->update($sub, [
                'status' => 'cancelled',
                'note' => $note ? mb_substr(trim(($sub->note ? $sub->note.' | ' : '').$note), 0, 500) : $sub->note,
            ]);
            $ids = $this->subscriptionAccounts->forBeneficiary($sub->user_id, $sub->items, create: false);
            $this->accounts->markDirty($ids);
            $this->dispatcher->afterCommit($ids);
            ActivityLog::log('hub.subscription.cancel', "إلغاء اشتراك #{$sub->id}", $sub, $old, $sub->toArray());

            return $sub;
        });
    }
}
