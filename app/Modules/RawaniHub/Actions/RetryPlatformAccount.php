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

/** «إعادة المحاولة»: failed or needs_info goes back to pending (design §3.3). */
final class RetryPlatformAccount
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformAccountRepository $accounts, private AccountSyncDispatcher $dispatcher) {}

    public function execute(User $actor, int $accountId): HubPlatformAccount
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($accountId) {
            $account = $this->accounts->find($accountId);
            if (! $account || ! in_array($account->status, ['failed', 'needs_info'], true)) {
                throw ValidationException::withMessages(['account' => 'only a failed or needs_info account can be retried']);
            }
            $account = $this->accounts->update($account, ['status' => 'pending', 'needs_sync' => true, 'attempts' => 0, 'last_error' => null]);
            ActivityLog::log('hub.account.retry', "إعادة محاولة حساب #{$account->id}", $account);
            $this->dispatcher->afterCommit([$account->id]);

            return $account;
        });
    }
}
