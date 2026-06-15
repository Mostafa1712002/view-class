<?php

namespace App\Modules\QuestionBankCore\Repositories\Contracts;

use App\Models\StudyWeek;
use Illuminate\Support\Collection;

/**
 * Study-week (الأسبوع الدراسي) data access (#248). Weeks scope INDIRECTLY through
 * academic_term_id → academic_year.school_id; the controller resolves the term to
 * the caller's scope before any write.
 */
interface WeekRepository
{
    /** Weeks for a given academic term (semester), ordered by sort_order (= رقم الأسبوع). */
    public function listForTerm(int $termId): Collection;

    public function find(int $id): ?StudyWeek;

    public function create(array $data): StudyWeek;

    public function update(StudyWeek $week, array $data): StudyWeek;

    public function delete(StudyWeek $week): bool;

    /** Bulk-delete by ids, scoped to a single term (defence-in-depth). */
    public function deleteMany(array $ids, int $termId): int;

    /** Insert many weeks for a term in one statement. */
    public function insertMany(array $rows): void;

    /** Whether a week-number (sort_order) already exists in the term. */
    public function numberExists(int $termId, int $number, ?int $ignoreId = null): bool;

    /**
     * Whether [start,end] overlaps any existing week in the term.
     */
    public function dateOverlaps(int $termId, string $start, string $end, ?int $ignoreId = null): bool;
}
