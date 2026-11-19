<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Services\AccountSyncDispatcher;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Regenerates the password of a managed account; one password per person (design §3.4), so every managed
 * account of that person is reset to the same value. Linked (non-managed) accounts are never touched.
 * Returns the new plain password for the admin screen; it is never logged.
 */
final class ResetAccountPassword
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformAccountRepository $accounts, private AccountSyncDispatcher $dispatcher) {}

    public function execute(User $actor, int $accountId): string
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($actor, $accountId) {
            $account = $this->accounts->find($accountId);
            if (! $account || ! $account->managed || ! $account->user_id) {
                throw ValidationException::withMessages(['account' => 'only a managed account can have its password reset']);
            }
            $password = Str::password(10, symbols: false);
            $ids = [];
            foreach ($this->accounts->forUser($account->user_id)->where('managed', true)->where('role', $account->role) as $row) {
                $this->accounts->update($row, ['password' => $password, 'needs_sync' => true]);
                $ids[] = $row->id;
            }
            ActivityLog::log('hub.account.reset_password', "إعادة تعيين كلمة مرور المستخدم #{$account->user_id}", $account, null, ['accounts' => $ids]);
            $this->dispatcher->afterCommit($ids);

            return $password;
        });
    }
}
