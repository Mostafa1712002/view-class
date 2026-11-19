<?php

namespace App\Modules\Whatsapp\Repositories\Contracts;

use Illuminate\Support\Collection;

interface RecipientRepository
{
    /**
     * Resolve a recipient group to a collection of User models.
     *
     * MUST be null-safe on $schoolId: when null (super-admin acting across all
     * schools) the school filter is skipped entirely — never produces a bare
     * `where('school_id', null)`.
     *
     * @param  string    $audience  group key (all_students, class_students, ...)
     * @param  int|null  $schoolId  active school, or null for all schools
     * @param  int|string|array|null  $refId  class ids / grade levels / stage levels (one or many)
     * @return Collection<int, \App\Models\User>
     */
    public function resolveAudience(string $audience, ?int $schoolId, int|string|array|null $refId = null): Collection;

    /**
     * Fetch specific users by id, school-scoped (null-safe).
     *
     * @param  array<int>  $ids
     * @return Collection<int, \App\Models\User>
     */
    public function findUsers(array $ids, ?int $schoolId): Collection;

    /**
     * Classes available for the "by class" dropdown, school-scoped via section.
     *
     * @return Collection<int, \App\Models\ClassRoom>
     */
    public function classesForSchool(?int $schoolId): Collection;

    /**
     * Distinct grade levels available for the "by grade" dropdown.
     *
     * @return array<int>
     */
    public function gradeLevelsForSchool(?int $schoolId): array;

    /**
     * Stages (sections.level => Arabic label) present in the school.
     *
     * @return array<string,string>
     */
    public function stagesForSchool(?int $schoolId): array;
}
