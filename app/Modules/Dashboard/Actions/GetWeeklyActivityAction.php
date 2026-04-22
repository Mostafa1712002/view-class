<?php

namespace App\Modules\Dashboard\Actions;

use App\Modules\Dashboard\Repositories\Contracts\DashboardStatsRepository;

final class GetWeeklyActivityAction
{
    public function __construct(private DashboardStatsRepository $repo) {}

    public function execute(?int $schoolId): array
    {
        return $this->repo->weeklyActivity($schoolId);
    }
}
