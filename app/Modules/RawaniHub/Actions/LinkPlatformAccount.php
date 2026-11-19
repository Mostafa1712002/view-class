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

/**
 * The ONLY path that links a Rawani person to a pre-existing platform account (conflict resolution, design §3.3/§4).
 * Two entry shapes:
 *  - $account is a `conflict` row of a Rawani person: pass $externalId (or it must already hold one from the 409 details);
 *  - $account is a mirrored, unlinked row (user_id NULL): pass $userId.
 * The link is non-managed, confirmed by the admin, never pushes a password, and goes back to `pending` for the sync job.
 */
final class LinkPlatformAccount
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformAccountRepository $accounts, private AccountSyncDispatcher $dispatcher) {}

    public function execute(User $actor, int $accountId, ?int $userId = null, ?string $externalId = null): HubPlatformAccount
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($actor, $accountId, $userId, $externalId) {
            $account = $this->accounts->find($accountId) ?? throw ValidationException::withMessages(['account' => 'not found']);
            $old = $account->makeHidden('password')->toArray();

            if ($account->user_id === null) {
                $userId ?? throw ValidationException::withMessages(['user_id' => 'required for an unlinked account']);
                in_array($account->role, ['student', 'parent'], true) // rawani/v1 writes students and parents only
                    || throw ValidationException::withMessages(['account' => __('hub_directory.teacher_readonly')]);
                User::findOrFail($userId);
                $keep = $account;
                $drop = $this->accounts->findForUser($account->platform_id, $account->role, $userId);
            } elseif ($account->status === 'conflict') {
                $externalId ??= $account->external_id;
                $externalId ?: throw ValidationException::withMessages(['external_id' => 'required to resolve a conflict']);
                $mirror = $this->accounts->findByExternal($account->platform_id, $account->role, $externalId);
                $keep = $mirror && $mirror->id !== $account->id ? $mirror : $account;
                $drop = $keep === $account ? null : $account;
                $userId = $account->user_id;
            } else {
                throw ValidationException::withMessages(['account' => 'only a conflict or an unlinked account can be linked']);
            }

            if ($drop && $drop->id !== $keep->id) {
                if ($drop->external_id && $drop->external_id !== $keep->external_id) {
                    throw ValidationException::withMessages(['user_id' => 'the person is already linked to another account']);
                }
                $drop->delete(); // frees the (platform, role, user) unique key for $keep
            }
            if ($keep->user_id && $keep->user_id !== $userId) {
                throw ValidationException::withMessages(['account' => 'already linked to another person']);
            }

            $keep = $this->accounts->update($keep, [
                'user_id' => $userId, 'external_id' => $externalId ?? $keep->external_id,
                'managed' => false, 'password' => null, 'link_confirmed_by' => $actor->id,
                'status' => 'pending', 'enabled' => true, 'needs_sync' => true, 'attempts' => 0, 'last_error' => null,
            ]);
            ActivityLog::log('hub.account.link', "ربط حساب {$keep->role} على المنصة #{$keep->platform_id} بالمستخدم #{$userId}", $keep, $old, $keep->makeHidden('password')->toArray());
            $this->dispatcher->afterCommit([$keep->id]);

            return $keep;
        });
    }
}
