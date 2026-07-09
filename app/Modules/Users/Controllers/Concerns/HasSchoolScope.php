<?php

namespace App\Modules\Users\Controllers\Concerns;

trait HasSchoolScope
{
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
