<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict role-permission gate for module routes whose access must be controlled
 * from the Roles screen.
 *
 * Unlike `permission:` (CheckPermission → canDo), this does NOT apply the
 * default-allow-for-".view" rule: a user reaches the route only if they are a
 * super-admin or their role actually holds the permission (permission_role).
 * This makes toggling a permission in the Roles UI truly control access to the
 * gated module, while leaving the global canDo() default-allow untouched
 * everywhere else. The baseline seed grants every role its current access, so
 * turning this on does not lock anyone out.
 */
class RolePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isSuperAdmin() || $user->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
    }
}
