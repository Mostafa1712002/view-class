<?php

namespace App\Modules\Gamification\Actions;

use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use App\Modules\Gamification\Repositories\Contracts\RewardRepository;
use Carbon\CarbonInterface;

/** Creates the (single) reward of each active period the student's points reached. Rewards are never revoked. */
final class CheckRewardThreshold
{
    public function __construct(private PointLedgerRepository $ledger, private RewardRepository $rewards) {}

    public function execute(int $userId, ?CarbonInterface $at = null): void
    {
        $user = $this->ledger->findUser($userId);
        if (! $user || ! $user->school_id) {
            return;
        }

        foreach ($this->rewards->activePeriods((int) $user->school_id, $user->section_id, $at ?? now()) as $period) {
            $sum = $this->rewards->periodSum($user->id, $period);
            if ($sum >= $period->min_points) {
                $this->rewards->createReward((int) $user->school_id, $user->id, $period->id, $sum);
            }
        }
    }
}
