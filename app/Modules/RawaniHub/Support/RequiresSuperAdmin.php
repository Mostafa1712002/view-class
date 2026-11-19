<?php

namespace App\Modules\RawaniHub\Support;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/** The hub is platform-level: only super-admins run its actions (design §3.1, defaults in §11). */
trait RequiresSuperAdmin
{
    private function authorizeHubAdmin(User $actor): void
    {
        if (! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Only super-admins manage the Rawani hub.');
        }
    }
}
