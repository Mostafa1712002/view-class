<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Use canDo() which: (1) bypasses for super-admin, (2) applies default-allow
        // when job-title has no configured permissions yet.
        if (!$request->user()->canDo($permission)) {
            abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء');
        }

        return $next($request);
    }
}
