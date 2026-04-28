<?php

namespace App\Modules\QuestionBanks\Repositories\Contracts;

use App\Models\QuestionBank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuestionBankRepository
{
    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator;

    public function findScoped(int $id, ?int $schoolId): ?QuestionBank;

    public function create(array $payload, array $subjectIds, array $memberRoles): QuestionBank;

    public function update(QuestionBank $bank, array $payload, ?array $subjectIds, ?array $memberRoles): QuestionBank;

    public function delete(QuestionBank $bank): void;

    public function library(): LengthAwarePaginator;

    public function clone(QuestionBank $template, ?int $schoolId, ?int $createdBy): QuestionBank;
}
