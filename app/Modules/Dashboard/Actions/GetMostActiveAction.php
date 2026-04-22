<?php

namespace App\Modules\Dashboard\Actions;

use App\Modules\Dashboard\Repositories\Contracts\DashboardStatsRepository;

final class GetMostActiveAction
{
    public function __construct(private DashboardStatsRepository $repo) {}

    public function execute(?int $schoolId, ?int $companyId): array
    {
        return $this->repo->mostActive($schoolId, $companyId);
    }
}
