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

        // A platform marked sso=false (no SSO page live yet) never gets a code burned: show the credentials instead.
        $result = ($platform->capabilities['sso'] ?? true)
            ? (new PlatformClient((string) $platform->base_url, (string) $platform->secret))->sso($account->role, $owner->id)
            : new \App\Modules\RawaniHub\DTOs\PlatformResult('unsupported', 501);

        if ($result->ok() && ! empty($result->data['url']) && self::onPlatformSite((string) $result->data['url'], (string) $platform->base_url)) {
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

    /**
     * The answer is not signed, so only follow a URL on the platform's own site: https (or the base URL's own scheme,
     * for a local http stub) and a host under the same registrable domain as base_url (e.g. dashboard.x.com → x.com SPA).
     * ponytail: "same last two labels" is wrong for multi-part TLDs (x.com.sa); keep an exact list per platform if one appears.
     */
    private static function onPlatformSite(string $url, string $baseUrl): bool
    {
        $u = parse_url($url);
        $b = parse_url($baseUrl);
        $host = strtolower($u['host'] ?? '');
        $baseHost = strtolower($b['host'] ?? '');
        $scheme = strtolower($u['scheme'] ?? '');
        if ($host === '' || $baseHost === '' || ! ($scheme === 'https' || $scheme === strtolower($b['scheme'] ?? ''))) {
            return false;
        }
        $site = fn (string $h) => filter_var($h, FILTER_VALIDATE_IP) ? $h : implode('.', array_slice(explode('.', $h), -2));

        return $host === $baseHost || $site($host) === $site($baseHost);
    }
}
