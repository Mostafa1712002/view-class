<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Detaches an admin-confirmed link in Rawani: the row goes back to an unlinked directory entry.
 * Rawani-created (managed) accounts are not unlinked; they are disabled instead.
 * rawani/v1 has no detach call, so the platform keeps its stored Rawani id and any Rawani grants until they end.
 */
final class UnlinkPlatformAccount
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformAccountRepository $accounts) {}

    public function execute(User $actor, int $accountId): HubPlatformAccount
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($accountId) {
            $account = $this->accounts->find($accountId) ?? throw ValidationException::withMessages(['account' => 'not found']);
            if (! $account->user_id || $account->managed || ! $account->link_confirmed_by) {
                throw ValidationException::withMessages(['account' => __('hub_directory.unlink_only_linked')]);
            }
            $old = $account->makeHidden('password')->toArray();
            $account = $this->accounts->update($account, [
                'user_id' => null, 'link_confirmed_by' => null, 'status' => 'unlinked',
                'needs_sync' => false, 'attempts' => 0, 'last_error' => null, 'password' => null, 'password_reset_pending' => false,
            ]);
            ActivityLog::log('hub.account.unlink', "فك ربط حساب {$account->role} #{$account->id} على المنصة #{$account->platform_id} عن المستخدم #{$old['user_id']}",
                $account, $old, $account->makeHidden('password')->toArray());

            return $account;
        });
    }
}
