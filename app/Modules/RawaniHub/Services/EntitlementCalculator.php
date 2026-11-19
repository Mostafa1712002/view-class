<?php

namespace App\Modules\RawaniHub\Services;

use App\Modules\RawaniHub\Models\HubSubscription;
use Carbon\CarbonInterface;

/** Pure window logic (design §3.2): no DB, the caller passes the beneficiary's subscriptions. */
class EntitlementCalculator
{
    /**
     * One window per product on one platform. A subscription counts when status is active, both
     * dates are set and ends_at is after $now (ends_at <= now is expired, same edge as expireDue).
     * Overlapping or touching intervals are merged; a gap is never bridged. The result is the
     * merged interval containing $now, or, when none does, the nearest future one.
     *
     * @param  iterable<HubSubscription>  $subscriptions
     * @return list<array{product:string,from:string,until:string}> sorted by product code
     */
    public function entitlements(iterable $subscriptions, int $platformId, CarbonInterface $now): array
    {
        $byProduct = [];
        foreach ($subscriptions as $s) {
            if ($s->status !== 'active' || ! $s->starts_at || ! $s->ends_at || $s->ends_at->lte($now)) {
                continue;
            }
            foreach ($s->items ?? [] as $item) {
                if ((int) $item['platform_id'] === $platformId) {
                    $byProduct[$item['product_code']][] = [$s->starts_at, $s->ends_at];
                }
            }
        }

        $windows = [];
        foreach ($byProduct as $code => $intervals) {
            usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);
            $merged = [];
            foreach ($intervals as [$from, $until]) {
                $last = count($merged) - 1;
                if ($last >= 0 && $from->lte($merged[$last]['until'])) {
                    if ($until->gt($merged[$last]['until'])) {
                        $merged[$last]['until'] = $until;
                    }
                } else {
                    $merged[] = ['from' => $from, 'until' => $until];
                }
            }
            // Every interval ends after now, so the first containing now or, failing that, the first one is right.
            $windows[$code] = collect($merged)->first(fn ($w) => $w['from']->lte($now)) ?? $merged[0];
        }
        ksort($windows);

        $out = [];
        foreach ($windows as $code => $w) {
            $out[] = [
                'product' => (string) $code,
                'from' => $w['from']->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                'until' => $w['until']->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            ];
        }

        return $out;
    }
}
