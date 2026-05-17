<?php

namespace App\Modules\GradeReports\Repositories\Contracts;

use App\Models\GradeReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GradeReportRepository
{
    public function paginate(?int $schoolId, ?string $type = null, int $perPage = 20): LengthAwarePaginator;

    public function findScoped(int $id, ?int $schoolId): ?GradeReport;

    public function createDynamic(array $payload, ?int $schoolId, int $createdBy): GradeReport;

    public function update(GradeReport $report, array $payload): GradeReport;

    public function replaceColumns(GradeReport $report, array $columns): GradeReport;

    public function delete(GradeReport $report): void;
}
