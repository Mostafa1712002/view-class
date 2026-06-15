<?php

namespace App\Modules\EducationalSites\Repositories\Contracts;

use App\Modules\EducationalSites\Models\EducationalSite;
use Illuminate\Support\Collection;

/**
 * #270 — Data access for educational sites. All school scoping lives here
 * (CLAUDE.md: Eloquent + multi-tenant scope belong inside the repository).
 *
 * The $scopeSchoolId argument is the resolved active school id:
 *   - null  → super-admin "see all" (every school + globals).
 *   - <int> → restrict to that school's sites.
 *
 * $includeGlobals controls whether global sites (school_id IS NULL) are also
 * manageable within a non-null scope. Only super-admins may manage globals, so
 * a scoped school-admin must pass `false` — otherwise they could edit/delete a
 * platform-wide site (cross-tenant write). Super-admins pass `true`.
 */
interface EducationalSiteRepository
{
    /** Management list (admin): all sites the actor may manage, ordered. */
    public function listForManagement(?int $scopeSchoolId, bool $includeGlobals = false, array $filters = []): Collection;

    /** Display list (end-users): only active sites visible to the viewer's school. */
    public function listVisible(?int $viewerSchoolId): Collection;

    /** Fetch a single site the actor may manage, or null if out of scope. */
    public function findManageable(int $id, ?int $scopeSchoolId, bool $includeGlobals = false): ?EducationalSite;

    public function create(array $attributes): EducationalSite;

    public function update(EducationalSite $site, array $attributes): EducationalSite;

    public function delete(EducationalSite $site): void;

    /** Persist sort_order per id ([id => order]) for sites the actor may manage. */
    public function reorder(array $orderById, ?int $scopeSchoolId, bool $includeGlobals = false): void;
}
