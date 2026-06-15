<?php

namespace App\Modules\QuestionBankCore\Repositories\Contracts;

use App\Models\BankQuestion;
use App\Models\QuestionBank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Data access for the rebuilt question-bank "core" screens (#249/#250/#253).
 *
 * Every read is school-scoped through the parent bank's school_id — never through
 * the loose classification columns on bank_questions. A null $schoolId means
 * "see all" and is only ever passed by a super-admin (enforced by the caller via
 * scopedSchoolId()).
 */
interface QuestionRepository
{
    /**
     * Paginated, filtered question list across all banks within scope.
     *
     * @param  array<string,mixed>  $filters
     */
    public function paginate(?int $schoolId, array $filters, int $perPage = 25): LengthAwarePaginator;

    /**
     * Find a single question, ensuring its bank is within school scope.
     */
    public function findScoped(int $questionId, ?int $schoolId): ?BankQuestion;

    /**
     * Find a bank within scope (used to resolve the bank a new question belongs to).
     */
    public function findBankScoped(int $bankId, ?int $schoolId): ?QuestionBank;

    /**
     * Banks selectable for the classification "bank" dropdown.
     *
     * @return \Illuminate\Support\Collection<int,QuestionBank>
     */
    public function banksForScope(?int $schoolId): \Illuminate\Support\Collection;

    /**
     * Whether the question is referenced by any exam (blocks hard-delete).
     */
    public function isUsedInExam(int $questionId): bool;
}
