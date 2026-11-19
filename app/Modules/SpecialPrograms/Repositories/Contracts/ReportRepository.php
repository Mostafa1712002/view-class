<?php

namespace App\Modules\SpecialPrograms\Repositories\Contracts;

use App\Modules\SpecialPrograms\DTOs\ReportFilters;
use Illuminate\Support\Collection;

interface ReportRepository
{
    public function studentResults(ReportFilters $f): Collection;

    public function bySubject(ReportFilters $f): Collection;

    public function bySkill(ReportFilters $f): Collection;

    public function byDifficulty(ReportFilters $f): Collection;

    public function examStats(ReportFilters $f): Collection;

    public function distribution(ReportFilters $f): Collection;

    public function byClass(ReportFilters $f): Collection;

    /** header, trend, subjects, skills|difficulty, history. */
    public function studentReport(int $studentId, string $program): array;

    public function teacherExamSummary(int $teacherId, ReportFilters $f): Collection;

    /** avg/max/min, top10, weak10, levels, per subject, per-student skills. */
    public function examReport(int $examId, ReportFilters $f): array;
}
