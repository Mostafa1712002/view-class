<?php

namespace App\Modules\Scope\Repositories\Contracts;

use App\Models\User;

interface ScopeRepository
{
    /** Companies visible to the given user. */
    public function companiesFor(User $user): array;

    /**
     * Schools under a company that the user is allowed to see. $companyId may be
     * the sentinel 'none' (بدون شركة) meaning "schools with no company". An
     * optional $compoundId further narrows to schools in that complex.
     */
    public function schoolsFor(User $user, int|string|null $companyId, ?int $compoundId = null): array;

    /**
     * Complexes (المجمعات) belonging to a company — the complex selector
     * cascades from company, so no company (null or 'none') yields none.
     */
    public function complexesFor(int|string|null $companyId): array;

    /** Academic years/semesters for a school the user can access. */
    public function yearsFor(User $user, ?int $schoolId): array;

    /** Academic terms (الفصول الدراسية) for a given academic year. */
    public function termsFor(User $user, ?int $yearId): array;

    public function companyExistsFor(User $user, int $companyId): bool;
    public function schoolExistsFor(User $user, int $schoolId): bool;
    public function complexExistsFor(User $user, int $complexId): bool;
    public function yearExistsFor(User $user, int $yearId): bool;
    public function termExistsFor(User $user, int $termId): bool;
}
