<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Services\PlatformClient;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * «افتح المنصة» (design §5, phase 5). Allowed for the account's owner or the owner's parent, on an active
 * account that is managed or admin-confirmed. The one-time URL is returned to be redirected to at once;
 * it is never stored or logged. A platform without SSO (501) falls back to the account's credentials.
 *
 * @return array{url:string}|array{credentials:array{login:?string,password:?string,platform:\App\Modules\RawaniHub\Models\HubPlatform}}
 */
final class IssueSsoLink
{
    public function __construct(private PlatformAccountRepository $accounts) {}

    public function execute(User $actor, int $accountId): array
    {
        $account = $this->accounts->find($accountId);
        $owner = $account?->user_id ? User::find($account->user_id) : null;
        $allowed = $owner && ($owner->id === $actor->id || $owner->parents()->whereKey($actor->id)->exists());
        if (! $allowed
            || $account->status !== 'active' || ! $account->enabled
            || ! ($account->managed || $account->link_confirmed_by)) {
            throw new AuthorizationException('account cannot be opened');
        }
        $platform = $account->platform;
        if (! $platform || ! $platform->is_enabled) {
            throw new AuthorizationException('platform is not enabled');
        }

        $result = (new PlatformClient((string) $platform->base_url, (string) $platform->secret))->sso($account->role, $owner->id);

        if ($result->ok() && ! empty($result->data['url'])) {
            ActivityLog::log('hub.account.open', "فتح حساب المنصة #{$account->id}", $account, null, ['by' => $actor->id]);

            return ['url' => (string) $result->data['url']];
        }
        if ($result->status === 'unsupported') {
            // Only managed accounts hold a Rawani-known password; a linked one never shows one.
            ActivityLog::log('hub.account.open_credentials', "عرض بيانات دخول الحساب #{$account->id}", $account, null, ['by' => $actor->id]);

            return ['credentials' => [
                'login' => $account->login,
                'password' => $account->managed ? $account->password : null,
                'platform' => $platform,
            ]];
        }

        throw ValidationException::withMessages(['account' => __('hub_open.failed')]);
    }
}
