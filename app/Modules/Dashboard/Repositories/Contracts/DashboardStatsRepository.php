<?php

namespace App\Modules\Dashboard\Repositories\Contracts;

interface DashboardStatsRepository
{
    public function counts(?int $schoolId): array;

    public function interactionRates(?int $schoolId): array;

    public function contentStats(?int $schoolId): array;

    public function variousStats(?int $schoolId): array;

    public function weeklyAbsenceRate(?int $schoolId): array;
}
