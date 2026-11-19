<?php

namespace App\Modules\RawaniHub\Repositories;

use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\Whatsapp\Support\PhoneNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentPlatformAccountRepository implements PlatformAccountRepository
{
    public function find(int $id): ?HubPlatformAccount
    {
        return HubPlatformAccount::with('platform')->find($id);
    }

    public function forUser(int $userId): Collection
    {
        return HubPlatformAccount::with('platform')->where('user_id', $userId)->orderBy('id')->get();
    }

    public function findForUser(int $platformId, string $role, int $userId): ?HubPlatformAccount
    {
        return HubPlatformAccount::where(['platform_id' => $platformId, 'role' => $role, 'user_id' => $userId])->first();
    }

    public function findByExternal(int $platformId, string $role, string $externalId): ?HubPlatformAccount
    {
        return HubPlatformAccount::where(['platform_id' => $platformId, 'role' => $role, 'external_id' => $externalId])->first();
    }

    public function firstOrCreateForUser(int $platformId, string $role, int $userId, array $attributes = []): HubPlatformAccount
    {
        return HubPlatformAccount::firstOrCreate(
            ['platform_id' => $platformId, 'role' => $role, 'user_id' => $userId],
            $attributes + ['status' => 'pending', 'managed' => true],
        );
    }

    public function update(HubPlatformAccount $account, array $attributes): HubPlatformAccount
    {
        $account->update($attributes);

        return $account;
    }

    public function markDirty(array $accountIds): void
    {
        HubPlatformAccount::whereIn('id', $accountIds)->update(['needs_sync' => true]);
    }

    public function dirtyForSync(CarbonInterface $olderThan): Collection
    {
        return HubPlatformAccount::where('needs_sync', true)
            ->whereIn('status', ['pending', 'active', 'disabled'])
            ->where('updated_at', '<', $olderThan) // updated_at = last attempt (the job writes attempts/status each try)
            ->get();
    }

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return HubPlatformAccount::with(['platform', 'user'])
            ->when($filters['platform_id'] ?? null, fn ($q, $v) => $q->where('platform_id', $v))
            ->when($filters['role'] ?? null, fn ($q, $v) => $q->where('role', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(isset($filters['linked']) && $filters['linked'] !== '', fn ($q) => $filters['linked']
                ? $q->whereNotNull('user_id') : $q->whereNull('user_id'))
            ->when(trim((string) ($filters['q'] ?? '')), function ($q, $v) {
                $digits = preg_replace('/[\s\-+]/', '', $v);
                $q->where(function ($w) use ($v, $digits) {
                    if (ctype_digit($digits)) {
                        $w->where('national_id', $digits)->orWhere('phone', PhoneNormalizer::normalize($v)); // both indexed
                    } elseif (str_contains($v, '@')) {
                        $w->where('email', mb_strtolower($v));
                    } else {
                        // ponytail: contains-match scans names (~100 ms at 62k rows locally); switch to prefix + index if it slows
                        $w->where('name', 'like', '%'.addcslashes($v, '%_\\').'%')->orWhere('login', $v);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    private const MIRROR_COLUMNS = ['name', 'email', 'phone', 'national_id', 'parent_external_id', 'org_label', 'access', 'platform_active', 'seen_at'];

    public function mirrorPage(int $platformId, array $items, CarbonInterface $seenAt): array
    {
        $rows = [];
        $rawani = [];
        foreach ($items as $item) {
            $row = is_array($item) ? $this->mirrorRow($platformId, $item, $seenAt) : null;
            if ($row) {
                $rows[$row['role'].':'.$row['external_id']] = $row; // a key twice in one page would fail the upsert
                if (is_numeric($item['rawani_id'] ?? null)) {
                    $rawani[$row['role'].':'.(int) $item['rawani_id']] = $row['external_id'];
                }
            }
        }
        if (! $rows) {
            return [];
        }

        // §3.1 rule 1: a provisioned row whose create timed out before Rawani stored the external_id.
        // Only fills a NULL external_id; a rawani_id never sets user_id (that would be an automatic link).
        if ($rawani) {
            $userIds = array_map(fn ($k) => (int) explode(':', $k)[1], array_keys($rawani));
            $waiting = HubPlatformAccount::where('platform_id', $platformId)->whereNull('external_id')->whereIn('user_id', $userIds)->get(['id', 'role', 'user_id']);
            foreach ($waiting as $acc) {
                $ext = $rawani[$acc->role.':'.$acc->user_id] ?? null;
                if ($ext !== null && ! $this->findByExternal($platformId, $acc->role, $ext)) {
                    HubPlatformAccount::whereKey($acc->id)->toBase()->update(['external_id' => $ext]);
                }
            }
        }

        $now = now();
        DB::table('hub_platform_accounts')->upsert(
            array_map(fn ($r) => $r + ['status' => 'unlinked', 'managed' => false, 'enabled' => true, 'needs_sync' => false,
                'attempts' => 0, 'created_at' => $now, 'updated_at' => $now], array_values($rows)),
            ['platform_id', 'role', 'external_id'],
            self::MIRROR_COLUMNS, // updated_at stays: it marks the sync job's last attempt (dirtyForSync)
        );

        // Back from `missing`: unlinked rows return to `unlinked`; linked rows are re-derived by the sync job.
        $revived = [];
        foreach (collect($rows)->groupBy('role') as $role => $group) {
            $back = HubPlatformAccount::where(['platform_id' => $platformId, 'role' => $role, 'status' => 'missing'])
                ->whereIn('external_id', $group->pluck('external_id'))->get(['id', 'user_id']);
            [$linked, $unlinked] = $back->partition(fn ($a) => $a->user_id !== null);
            if ($unlinked->isNotEmpty()) {
                HubPlatformAccount::whereIn('id', $unlinked->pluck('id'))->toBase()->update(['status' => 'unlinked']);
            }
            if ($linked->isNotEmpty()) {
                HubPlatformAccount::whereIn('id', $linked->pluck('id'))->update(['status' => 'pending', 'needs_sync' => true, 'attempts' => 0]);
                $revived = array_merge($revived, $linked->pluck('id')->all());
            }
        }

        return $revived;
    }

    /** Whitelisted mirror columns only: an item's other keys (anything secret a platform might add) are never stored. */
    private function mirrorRow(int $platformId, array $item, CarbonInterface $seenAt): ?array
    {
        $role = $item['role'] ?? null;
        $ext = self::cut($item['external_id'] ?? null, 64);
        if (! in_array($role, ['student', 'parent', 'teacher'], true) || $ext === null) {
            return null;
        }
        $email = self::cut($item['email'] ?? null, 255);
        $access = is_array($item['access'] ?? null)
            ? array_values(array_map(fn ($a) => array_intersect_key((array) $a, array_flip(['product', 'until', 'source'])), $item['access']))
            : null;

        return [
            'platform_id' => $platformId, 'role' => $role, 'external_id' => $ext,
            'name' => self::cut($item['name'] ?? null, 255),
            'email' => $email === null ? null : mb_strtolower($email),
            'phone' => self::cut(PhoneNormalizer::normalize(is_scalar($item['phone'] ?? null) ? (string) $item['phone'] : null), 64),
            'national_id' => self::cut($item['national_id'] ?? null, 64),
            'parent_external_id' => self::cut($item['parent_external_id'] ?? null, 64),
            'org_label' => self::cut($item['org'] ?? null, 255),
            'access' => $access === null ? null : json_encode($access, JSON_UNESCAPED_UNICODE),
            'platform_active' => array_key_exists('active', $item) && $item['active'] !== null ? (bool) $item['active'] : null,
            'seen_at' => $seenAt,
        ];
    }

    private static function cut(mixed $value, int $max): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    public function sweepMissing(int $platformId, CarbonInterface $startedAt): int
    {
        if (! HubPlatformAccount::where('platform_id', $platformId)->where('seen_at', '>=', $startedAt)->exists()) {
            return 0; // an empty walk (a platform bug, a wiped table) never marks everything missing
        }

        return HubPlatformAccount::where('platform_id', $platformId)->whereNotNull('external_id')->where('status', '!=', 'missing')
            ->where(fn ($q) => $q->whereNull('seen_at')->orWhere('seen_at', '<', $startedAt))
            ->where('updated_at', '<', $startedAt) // created or synced during the walk: next sweep decides
            ->toBase()->update(['status' => 'missing']);
    }

    public function relatives(HubPlatformAccount $account): Collection
    {
        $q = HubPlatformAccount::with('user')->where('platform_id', $account->platform_id);
        if ($account->role === 'student' && $account->parent_external_id) {
            return $q->where(['role' => 'parent', 'external_id' => $account->parent_external_id])->get();
        }
        if ($account->role === 'parent' && $account->external_id) {
            return $q->where(['role' => 'student', 'parent_external_id' => $account->external_id])->orderBy('name')->limit(50)->get();
        }

        return collect();
    }

    public function unlinkedByIdentifiers(int $platformId, string $role, array $identifiers): Collection
    {
        $identifiers = array_filter($identifiers, fn ($v) => $v !== null && $v !== '');
        if (! $identifiers) {
            return collect();
        }

        return HubPlatformAccount::where(['platform_id' => $platformId, 'role' => $role])->whereNull('user_id')
            ->where(function ($w) use ($identifiers) {
                foreach ($identifiers as $column => $value) {
                    $w->orWhere($column, $value);
                }
            })->limit(20)->get();
    }
}
