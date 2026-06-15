<?php

namespace App\Modules\QuestionBankCore\Repositories\Contracts;

use App\Modules\QuestionBankCore\Models\Passage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Data access for reading passages / القطع القرائية (#252).
 *
 * Every read is school-scoped through the passage's parent bank school_id — the
 * same fail-closed semantics as the question repository. A null $schoolId means
 * "see all" and is only ever passed by a super-admin (enforced by the caller via
 * scopedSchoolId()).
 */
interface PassageRepository
{
    /**
     * Paginated, filtered passage list within scope (with child-question counts).
     *
     * @param  array<string,mixed>  $filters
     */
    public function paginate(?int $schoolId, array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a single passage within scope (with its child questions + answers).
     */
    public function findScoped(int $passageId, ?int $schoolId): ?Passage;
}
