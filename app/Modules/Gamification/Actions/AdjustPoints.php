<?php

namespace App\Modules\Gamification\Actions;

use App\Models\User;
use App\Modules\Gamification\Models\PointTransaction;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use Illuminate\Support\Facades\DB;

/** Manual correction: one immutable `manual` row (reason required). */
final class AdjustPoints
{
    public function __construct(private PointLedgerRepository $ledger, private CheckRewardThreshold $thresholds) {}

    public function execute(User $target, int $points, string $reason, int $actorId): void
    {
        DB::transaction(function () use ($target, $points, $reason, $actorId) {
            $revision = $this->ledger->keyRowsForUpdate($target->id, 'manual', 'manual', 0)->count();
            $this->ledger->insert([
                'school_id' => (int) $target->school_id, 'user_id' => $target->id,
                'rule_key' => 'manual', 'source_type' => 'manual', 'source_id' => 0,
                'revision' => $revision, 'points' => $points, 'kind' => 'manual',
                'reason' => $reason, 'meta' => null, 'created_by' => $actorId, 'created_at' => now(),
            ]);
        }, 3);

        if ($points > 0 && $target->isStudent()) {
            $this->thresholds->execute($target->id);
        }
    }
}
