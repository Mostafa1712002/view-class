<?php

namespace App\Modules\QuestionBanks\Repositories\Contracts;

use App\Models\QuestionBank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuestionBankRepository
{
    /**
     * Paginate banks visible to the active school, with optional filters.
     *
     * @param array $filters keys: q, visibility, status, source, subject_id, grade_level, creator_id
     */
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * KPI counters for the index header.
     *
     * @return array{total:int, public:int, private:int, active:int}
     */
    public function stats(?int $schoolId): array;

    public function findScoped(int $id, ?int $schoolId): ?QuestionBank;

    public function create(array $payload, array $subjectIds, array $memberRoles, array $schoolIds = []): QuestionBank;

    public function update(QuestionBank $bank, array $payload, ?array $subjectIds, ?array $memberRoles, ?array $schoolIds = null): QuestionBank;

    public function delete(QuestionBank $bank): void;

    public function library(): LengthAwarePaginator;

    public function clone(QuestionBank $template, ?int $schoolId, ?int $createdBy): QuestionBank;

    /**
     * Approve a bank (under_review → active). Returns the updated bank.
     */
    public function approve(QuestionBank $bank): QuestionBank;

    /**
     * Promote a private bank to general (public). Super-admin only gate enforced in controller.
     * Returns the updated bank.
     */
    public function promote(QuestionBank $bank, array $schoolIds = []): QuestionBank;

    /**
     * Copy all approved questions from a general bank into a new private bank
     * owned by $targetSchoolId. Returns the new bank.
     */
    public function copyToSchool(QuestionBank $generalBank, int $targetSchoolId, int $createdBy): QuestionBank;
}
