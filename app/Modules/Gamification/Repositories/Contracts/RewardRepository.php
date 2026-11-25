<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Modules\Gamification\Models\PointReward;
use App\Modules\Gamification\Models\PointRewardPeriod;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RewardRepository
{
    public function periods(?int $schoolId): Collection;

    public function findPeriod(int $id, ?int $schoolId): ?PointRewardPeriod;

    public function createPeriod(array $attributes): PointRewardPeriod;

    public function updatePeriod(PointRewardPeriod $period, array $attributes): PointRewardPeriod;

    public function deletePeriod(PointRewardPeriod $period): void;

    /** Periods of the school covering $date for a student of $sectionId, latest starts_on first. */
    public function activePeriods(int $schoolId, ?int $sectionId, CarbonInterface $date): Collection;

    /** Points earned inside the period; ends_on is inclusive. */
    public function periodSum(int $userId, PointRewardPeriod $period): int;

    /** Inserts one reward per (period, user); null when it already exists. */
    public function createReward(int $schoolId, int $userId, int $periodId, int $points): ?PointReward;

    public function rewards(?int $schoolId, ?string $status = null, int $perPage = 25): LengthAwarePaginator;

    public function findReward(int $id, ?int $schoolId): ?PointReward;

    public function deliver(PointReward $reward, int $deliveredBy): PointReward;

    /** Top $limit by points earned in $month (Y-m); type student|teacher|vice. */
    public function honourList(int $schoolId, string $month, string $type, ?int $sectionId = null, int $limit = 10, ?int $classId = null, ?int $subjectId = null): Collection;
}
