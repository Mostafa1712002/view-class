<?php

namespace App\Modules\QuestionBankCore\Repositories\Contracts;

use App\Modules\QuestionBankCore\Models\Skill;
use Illuminate\Support\Collection;

interface SkillRepository
{
    /**
     * Skills visible to a school scope. A null scope (super-admin see-all) returns
     * every skill; otherwise rows scoped to the school or globally shared (school_id null).
     */
    public function listForScope(?int $schoolId): Collection;

    /** Skills filtered by the taxonomy a question form needs (subject/term/week). */
    public function listForQuestionForm(?int $schoolId, ?int $subjectId, ?int $semesterId, ?int $weekId): Collection;

    /**
     * Paginated admin list (incl. inactive), school-scoped, with optional filters
     * (q name, subject_id, semester_id, status).
     *
     * @param  array<string,mixed>  $filters
     */
    public function paginateForAdmin(?int $schoolId, array $filters, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /** Bulk-insert skills (used by the Excel import). */
    public function insertMany(array $rows): void;

    public function find(int $id): ?Skill;

    public function create(array $data): Skill;

    public function update(Skill $skill, array $data): Skill;

    public function delete(Skill $skill): bool;
}
