<?php

namespace App\Modules\Users\Controllers\Concerns;

use App\Models\School;
use Illuminate\Http\Request;

trait HasSchoolScope
{
    /**
     * Schools the current actor may assign a user to. Super-admin → every
     * school; anyone else → empty (their school is forced, so the create/edit
     * forms hide the picker). Mirrors the multi-school picker from card #307.
     */
    protected function assignableSchools()
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return collect();
        }

        return School::orderBy('sort_order')->orderBy('name_ar')
            ->get(['id', 'name', 'name_ar', 'name_en']);
    }

    /**
     * Resolve which school a create/edit writes to. A super-admin picks it
     * explicitly via the required 'school_id' field; everyone else is forced
     * to their own active scope and can never file a user under another school.
     */
    protected function writeSchoolId(Request $request): ?int
    {
        if (auth()->user()?->isSuperAdmin()) {
            return (int) $request->input('school_id') ?: null;
        }

        return $this->activeSchoolId();
    }

    /**
     * Resolve the active school id from the authenticated user, falling back
     * to a session-stored scope (used by super-admin to switch schools).
     */
    protected function activeSchoolId(): ?int
    {
        $u = auth()->user();
        if (!$u) {
            return null;
        }
        if ($u->isSuperAdmin()) {
            // The navbar scope selector stores the active school under
            // session('scope.school_id'); keep the legacy key as a fallback.
            $scoped = session('scope.school_id') ?? session('admin.scope.school_id');
            return (int) ($scoped ?: $u->school_id) ?: null;
        }
        // Multi-school admins (card #307) may switch among their linked schools
        // via the header scope selector; honour it when the school is allowed.
        $scoped = session('scope.school_id');
        if ($scoped && in_array((int) $scoped, $u->managedSchoolIds(), true)) {
            return (int) $scoped;
        }
        return $u->school_id;
    }

    /**
     * Active school id for data scoping, fail-closed: a null scope (see-all)
     * is only permitted for super-admins. Any non-super-admin that resolves to
     * a null school is denied rather than silently shown every tenant's data.
     */
    protected function scopedSchoolId(): ?int
    {
        $id = $this->activeSchoolId();
        abort_if($id === null && ! (auth()->user()?->isSuperAdmin() ?? false), 403);

        return $id;
    }

    /**
     * Drop null entries so columns with NOT NULL + default can fall back
     * to the legacy schema's default value.
     */
    protected function withoutNulls(array $payload): array
    {
        return array_filter($payload, static fn ($v) => $v !== null);
    }
}
