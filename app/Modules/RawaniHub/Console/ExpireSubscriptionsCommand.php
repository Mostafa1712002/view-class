<?php

namespace App\Modules\RawaniHub\Console;

use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Services\SubscriptionAccounts;
use Illuminate\Console\Command;

/** Marks due subscriptions expired and dirties the accounts they affect so the entitlement is pushed. */
class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'hub:expire';

    protected $description = 'Expire due hub subscriptions and sync the affected accounts';

    public function handle(SubscriptionRepository $subscriptions, SubscriptionAccounts $resolver, PlatformAccountRepository $accounts, AccountSyncDispatcher $dispatcher): int
    {
        $due = $subscriptions->expireDue(now());
        $ids = [];
        foreach ($due as $sub) {
            $ids = array_merge($ids, $resolver->forBeneficiary($sub->user_id, $sub->items ?? [], false));
        }
        $ids = array_values(array_unique($ids));
        $accounts->markDirty($ids);
        $dispatcher->afterCommit($ids);
        $this->info("expired {$due->count()}, accounts ".count($ids));

        return self::SUCCESS;
    }
}
