<?php

namespace App\Modules\Gamification\Actions;

use App\Modules\Gamification\Models\PointReward;
use App\Modules\Gamification\Repositories\Contracts\RewardRepository;

final class DeliverReward
{
    public function __construct(private RewardRepository $rewards) {}

    public function execute(PointReward $reward, int $actorId): PointReward
    {
        return $reward->status === PointReward::DELIVERED ? $reward : $this->rewards->deliver($reward, $actorId);
    }
}
