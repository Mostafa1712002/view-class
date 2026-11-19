<?php

namespace App\Modules\RawaniHub\Services;

use App\Modules\RawaniHub\Models\HubSubscription;
use Carbon\CarbonInterface;

/** Pure window logic (design §3.2): no DB, the caller passes the beneficiary's subscriptions. */
class EntitlementCalculator
{
    /**
     * Window per product on one platform: from = min(starts_at), until = max(ends_at) over the
     * subscriptions that count. A subscription counts when status is active, both dates are set
     * and ends_at is after $now (ends_at <= now is expired, same edge as expireDue).
     * Future starts count, so a renewal queued behind the current one stays in the window.
     * ponytail: min/max spans a gap between non-contiguous subscriptions; renewals stack so none is expected.
     *
     * @param  iterable<HubSubscription>  $subscriptions
     * @return list<array{product:string,from:string,until:string}> sorted by product code
     */
    public function entitlements(iterable $subscriptions, int $platformId, CarbonInterface $now): array
    {
        $windows = [];
        foreach ($subscriptions as $s) {
            if ($s->status !== 'active' || ! $s->starts_at || ! $s->ends_at || $s->ends_at->lte($now)) {
                continue;
            }
            foreach ($s->items ?? [] as $item) {
                if ((int) $item['platform_id'] !== $platformId) {
                    continue;
                }
                $code = $item['product_code'];
                $w = $windows[$code] ?? ['from' => $s->starts_at, 'until' => $s->ends_at];
                $windows[$code] = [
                    'from' => $s->starts_at->lt($w['from']) ? $s->starts_at : $w['from'],
                    'until' => $s->ends_at->gt($w['until']) ? $s->ends_at : $w['until'],
                ];
            }
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
