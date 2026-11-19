<?php

namespace App\Modules\RawaniHub\Services;

use App\Models\User;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\Whatsapp\Support\PhoneNormalizer;
use Illuminate\Support\Collection;

/**
 * Link suggestions for the admin (design §3.5, task 4.3): national ID, then a real email, then the phone.
 * Never by name, and never applied automatically (decision Q3): the admin picks one and confirms.
 */
class LinkCandidates
{
    public function __construct(private PlatformAccountRepository $accounts) {}

    /** Rawani people (same role, not yet linked on this platform) for an unlinked mirror row. @return Collection<int,array{user:User,reason:string}> */
    public function peopleFor(HubPlatformAccount $account, ?string $lookup = null): Collection
    {
        if (! in_array($account->role, ['student', 'parent'], true)) {
            return collect(); // the contract writes students and parents only (§4.3)
        }
        $found = collect();
        foreach ($this->identifiers($account->national_id, $account->email, $account->phone) as $column => $value) {
            $found = $found->merge($this->people($account, fn ($q) => $q->where($column, $value))->map(fn ($u) => ['user' => $u, 'reason' => $column]));
        }
        $lookup = trim((string) $lookup);
        if ($lookup !== '') { // the admin's own exact identifier (still never a name)
            $found = $found->merge($this->people($account, fn ($q) => $q->where(fn ($w) => $w->where('national_id', $lookup)
                ->orWhere('email', mb_strtolower($lookup))->orWhere('username', $lookup)->orWhere('phone', $lookup)))
                ->map(fn ($u) => ['user' => $u, 'reason' => 'lookup']));
        }

        return $found->unique(fn ($c) => $c['user']->id)->values();
    }

    /** Unlinked mirror rows for a person's `conflict` row, the 409's own account first. @return Collection<int,array{account:HubPlatformAccount,reason:string}> */
    public function accountsFor(HubPlatformAccount $account): Collection
    {
        $user = $account->user;
        $ids = $user ? $this->identifiers($user->national_id, $user->email, $user->phone) : [];
        $rows = $this->accounts->unlinkedByIdentifiers($account->platform_id, $account->role, $ids);
        $found = collect();
        if ($ext = $account->conflictExternalId()) {
            $hit = $this->accounts->findByExternal($account->platform_id, $account->role, $ext);
            $hit && $found->push(['account' => $hit, 'reason' => 'conflict']);
        }
        foreach ($ids as $column => $value) {
            $found = $found->merge($rows->filter(fn ($r) => $r->{$column} === $value)->map(fn ($r) => ['account' => $r, 'reason' => $column]));
        }

        return $found->unique(fn ($c) => $c['account']->id)->values();
    }

    /** null for the logins Rawani generates itself ({username}@viewclass.local, aafaq rw<id>@rawaniedu.com). */
    public static function realEmail(?string $email): ?string
    {
        $email = mb_strtolower(trim((string) $email));

        return $email === '' || str_ends_with($email, '@viewclass.local')
            || preg_match('/^rw\d+@'.preg_quote(AccountStateBuilder::AAFAQ_PLACEHOLDER_DOMAIN, '/').'$/', $email) ? null : $email;
    }

    /** @return array<string,string> in ranking order */
    private function identifiers(?string $nationalId, ?string $email, ?string $phone): array
    {
        return array_filter([
            'national_id' => trim((string) $nationalId) ?: null,
            'email' => self::realEmail($email),
            'phone' => PhoneNormalizer::normalize($phone), // digits only: 05… and +9665… do not meet
        ]);
    }

    private function people(HubPlatformAccount $account, \Closure $where): Collection
    {
        return User::whereHas('roles', fn ($r) => $r->where('slug', $account->role))
            ->where($where)
            ->whereNotExists(fn ($q) => $q->from('hub_platform_accounts as linked')->whereColumn('linked.user_id', 'users.id')
                ->where('linked.platform_id', $account->platform_id)->where('linked.role', $account->role)->whereNotNull('linked.external_id'))
            ->limit(20)->get();
    }
}
