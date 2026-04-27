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
            return (int) (session('admin.scope.school_id') ?: $u->school_id) ?: null;
        }
        return $u->school_id;
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
