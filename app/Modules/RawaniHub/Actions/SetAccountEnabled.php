<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Sets the desired enabled state; the sync job applies it (disable = blocked on the platform). */
final class SetAccountEnabled
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformAccountRepository $accounts, private AccountSyncDispatcher $dispatcher) {}

    public function execute(User $actor, int $accountId, bool $enabled): HubPlatformAccount
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($actor, $accountId, $enabled) {
            $account = $this->accounts->find($accountId);
            if (! $account || ! $account->user_id) {
                throw ValidationException::withMessages(['account' => 'only an account linked to a person can be enabled or disabled']);
            }
            $old = ['enabled' => $account->enabled];
            $account = $this->accounts->update($account, ['enabled' => $enabled, 'needs_sync' => true]);
            ActivityLog::log('hub.account.'.($enabled ? 'enable' : 'disable'), ($enabled ? 'تفعيل' : 'تعطيل')." حساب #{$account->id}", $account, $old, ['enabled' => $enabled]);
            $this->dispatcher->afterCommit([$account->id]);

            return $account;
        });
    }
}
