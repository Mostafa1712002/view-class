<?php

namespace App\Modules\RawaniHub\Jobs;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\DTOs\PlatformResult;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubProfile;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\SubscriptionRepository;
use App\Modules\RawaniHub\Services\AccountStateBuilder;
use App\Modules\RawaniHub\Services\PlatformClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pushes the absolute state of one platform account (design §3.3). Safe to run twice: the state is
 * rebuilt each run and the platform call is an idempotent PUT. Never logs or stores request bodies.
 */
class SyncPlatformAccountJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 6;

    public const BACKOFF = [60, 300, 900, 3600, 21600];

    /** Lock outlives the whole backoff chain (~7.4 h), but a lost job no longer blocks the account forever. */
    public int $uniqueFor = 28800;

    public function __construct(public int $accountId) {}

    public function backoff(): array
    {
        return self::BACKOFF;
    }

    public function uniqueId(): string
    {
        return (string) $this->accountId;
    }

    public function handle(PlatformAccountRepository $accounts, SubscriptionRepository $subscriptions, AccountStateBuilder $builder): void
    {
        $account = $accounts->find($this->accountId);
        if (! $account || ! $account->needs_sync || ! $account->user_id
            || ! in_array($account->status, ['pending', 'needs_info', 'active', 'disabled'], true)) {
            return;
        }
        $platform = $account->platform;
        if (! $platform || ! $platform->is_enabled || ! $platform->secret) {
            return; // stays dirty; picked up once the platform is switched on and has a secret
        }
        $user = User::find($account->user_id);
        if (! $user) {
            return;
        }

        $parent = null;
        if ($account->role === 'student' && ($platform->capabilities['parents'] ?? false)) {
            $parentId = DB::table('parent_student')->where('student_id', $user->id)
                ->orderByDesc('is_primary')->orderBy('id')->value('parent_id');
            $parent = $parentId ? User::find($parentId) : null;
            $parentAccount = $parent ? $accounts->findForUser($platform->id, 'parent', $parent->id) : null;
            if ($parentAccount && $parentAccount->status !== 'active') {
                return; // waits, no HTTP; woken when the parent turns active
            }
        }

        $create = $account->external_id === null;
        $sendPassword = $create || $account->password_reset_pending;
        if ($sendPassword && $account->managed && ! $account->password) {
            // One password per person: reuse the first managed account's, else generate (stored before the call so a retry reuses it).
            $first = $accounts->forUser($user->id)->first(fn ($a) => $a->managed && $a->password);
            $account->password = $first?->password ?? Str::password(10, symbols: false);
            $account->save();
        }

        $state = $builder->build(
            $account, $platform, $user, HubProfile::find($user->id),
            $subscriptions->activeForUser($user->id), now(), $parent, $sendPassword && $account->managed,
        );

        if (! $state->ready()) {
            $this->settle($account, ['status' => 'needs_info', 'needs_sync' => false, 'last_error' => 'missing: '.implode(', ', $state->missing)]);

            return;
        }

        $account->forceFill(['needs_sync' => false])->save(); // claim: a change during the call re-dirties and is not lost
        $client = new PlatformClient((string) $platform->base_url, (string) $platform->secret);
        $result = $client->putAccount($account->role, $user->id, $state->toPayload());

        $this->apply($account, $result, $accounts, $state->password !== null);
    }

    private function apply(HubPlatformAccount $account, PlatformResult $r, PlatformAccountRepository $accounts, bool $sentPassword): void
    {
        $platform = $account->platform;

        if ($r->ok()) {
            $this->settle($account, [
                'status' => $account->enabled ? 'active' : 'disabled',
                'external_id' => $r->data['external_id'] ?? $account->external_id,
                'login' => $r->data['login'] ?? $account->login,
                'managed' => $r->data['managed'] ?? $account->managed,
                'attempts' => 0, 'last_error' => null, 'synced_at' => now(),
            ]);
            if ($sentPassword) {
                // Clear the reset flag only if no newer reset landed during the call (the stored ciphertext is the one sent).
                HubPlatformAccount::whereKey($account->id)->where('password', $account->getRawOriginal('password'))
                    ->update(['password_reset_pending' => false]);
            }
            $platform->forceFill(['last_ok_at' => now(), 'last_error' => null])->save();
            if ($account->role === 'parent' && $account->status === 'active') {
                $this->wakeChildren($account, $accounts);
            }

            return;
        }

        $error = $this->describe($r);
        if ($r->status === PlatformResult::CONFLICT && in_array($r->code, ['ACCOUNT_EXISTS', 'IDENTIFIER_TAKEN'], true)) {
            $this->settle($account, ['status' => 'conflict', 'last_error' => $error]);
        } elseif ($r->status === PlatformResult::AUTH) { // a wrong secret or clock does not heal by waiting (design §3.3)
            $platform->forceFill(['last_error' => 'auth failing: '.$error])->save();
            $this->settle($account, ['status' => 'failed', 'last_error' => $error]);
        } elseif ($r->isRetryable()) {
            $this->retryOrFail($account, $error);
        } else { // invalid, other 409, not_found, unsupported: settles at once
            $this->settle($account, ['status' => 'failed', 'last_error' => $error]);
        }
    }

    private function retryOrFail(HubPlatformAccount $account, string $error): void
    {
        $attempt = $this->attempts();
        if ($attempt >= $this->tries) {
            $this->settle($account, ['status' => 'failed', 'attempts' => $attempt, 'last_error' => $error]);

            return;
        }
        $this->settle($account, ['needs_sync' => true, 'attempts' => $attempt, 'last_error' => $error]);
        $this->release(self::BACKOFF[$attempt - 1] ?? end(self::BACKOFF));
    }

    private function wakeChildren(HubPlatformAccount $parent, PlatformAccountRepository $accounts): void
    {
        $childIds = DB::table('parent_student')->where('parent_id', $parent->user_id)->pluck('student_id');
        $ids = HubPlatformAccount::where(['platform_id' => $parent->platform_id, 'role' => 'student', 'status' => 'pending'])
            ->whereIn('user_id', $childIds)->pluck('id')->all();
        $accounts->markDirty($ids);
        foreach ($ids as $id) {
            dispatch(new self($id));
        }
    }

    private function settle(HubPlatformAccount $account, array $attributes): void
    {
        $from = $account->status;
        $account->forceFill($attributes)->save();
        if (in_array($account->status, ['conflict', 'failed'], true) && $from !== $account->status) {
            self::audit($account, $from);
        }
    }

    /** Design §3.7: every transition into conflict or failed leaves an audit row (no user: the queue did it). */
    private static function audit(HubPlatformAccount $account, string $from): void
    {
        ActivityLog::log("hub.account.{$account->status}", "حساب #{$account->id} على المنصة #{$account->platform_id}: {$account->status}",
            $account, ['status' => $from], ['status' => $account->status, 'last_error' => $account->last_error]);
    }

    private function describe(PlatformResult $r): string
    {
        return mb_substr(trim(($r->code ?? $r->status).' '.($r->message ?? '').' '
            .($r->details ? json_encode($r->details, JSON_UNESCAPED_UNICODE) : '')), 0, 500);
    }

    public function failed(Throwable $e): void
    {
        $account = HubPlatformAccount::find($this->accountId);
        if (! $account) {
            return;
        }
        $from = $account->status;
        $account->forceFill(['status' => 'failed', 'last_error' => 'job crashed: '.class_basename($e)])->save();
        if ($from !== 'failed') {
            self::audit($account, $from);
        }
    }
}
