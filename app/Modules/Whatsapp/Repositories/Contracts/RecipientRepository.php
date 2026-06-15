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
     * @param  int|null  $refId     class id / grade level / etc. (audience-specific)
     * @return Collection<int, \App\Models\User>
     */
    public function resolveAudience(string $audience, ?int $schoolId, ?int $refId = null): Collection;

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
}
