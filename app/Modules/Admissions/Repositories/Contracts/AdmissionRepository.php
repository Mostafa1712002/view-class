<?php

namespace App\Modules\Admissions\Repositories\Contracts;

use App\Modules\Admissions\Models\AdmissionApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Data access for the Admissions module. All school-scoped reads accept a
 * nullable $schoolId: null means "see all" and is reserved for super-admins by
 * the caller (HasSchoolScope::scopedSchoolId fails closed before reaching here).
 */
interface AdmissionRepository
{
    /** Paginated, filtered list of applications scoped to a school (null = all). */
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** Unpaginated filtered list — used for exports. */
    public function all(?int $schoolId, array $filters = []): Collection;

    /** Find one application within scope, or null. */
    public function find(int $id, ?int $schoolId): ?AdmissionApplication;

    /** Status counts keyed by status slug, scoped. */
    public function statusCounts(?int $schoolId): array;

    /** Persist a new application (public form). */
    public function create(array $attributes): AdmissionApplication;

    public function update(AdmissionApplication $application, array $attributes): AdmissionApplication;

    public function delete(AdmissionApplication $application): void;

    /** Generate a unique application code. */
    public function nextCode(): string;
}
