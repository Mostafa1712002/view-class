<?php

namespace App\Modules\Scope\Repositories\Contracts;

use App\Models\User;

interface ScopeRepository
{
    /** Companies visible to the given user. */
    public function companiesFor(User $user): array;

    /** Schools under a company that the user is allowed to see. */
    public function schoolsFor(User $user, ?int $companyId): array;

    /** Academic years/semesters for a school the user can access. */
    public function yearsFor(User $user, ?int $schoolId): array;

    public function companyExistsFor(User $user, int $companyId): bool;
    public function schoolExistsFor(User $user, int $schoolId): bool;
    public function yearExistsFor(User $user, int $yearId): bool;
}
