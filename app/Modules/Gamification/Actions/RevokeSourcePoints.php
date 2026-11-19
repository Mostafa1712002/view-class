<?php

namespace App\Modules\Gamification\Actions;

use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;

/** Brings every key of a source to 0. Call it AFTER the source delete succeeded. */
final class RevokeSourcePoints
{
    public function __construct(private PointLedgerRepository $ledger, private SyncActivityPoints $sync) {}

    public function execute(string $sourceType, int $sourceId, ?string $reason = null): void
    {
        foreach ($this->ledger->sourceKeys($sourceType, $sourceId) as $key) {
            $this->sync->execute(new ActivityPoints(
                $key->rule_key, (int) $key->user_id, $sourceType, $sourceId, 0.0, auth()->id(), $reason,
            ));
        }
    }
}
