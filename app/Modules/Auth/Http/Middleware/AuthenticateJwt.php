<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Models\User;
use App\Modules\Auth\Services\JwtService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class AuthenticateJwt
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->bearerToken();
        if (! $header) {
            return ApiResponse::fail('UNAUTHENTICATED', 'Missing Bearer token.', 401);
        }

        try {
            $claims = $this->jwt->decode($header);
        } catch (\Throwable) {
            return ApiResponse::fail('UNAUTHENTICATED', 'Invalid or expired token.', 401);
        }

        if (($claims['typ'] ?? null) !== 'access' || ! isset($claims['sub'])) {
            return ApiResponse::fail('UNAUTHENTICATED', 'Invalid token.', 401);
        }

        $user = User::find($claims['sub']);
        if (! $user || ! ($user->is_active ?? true) || ($user->status ?? 'active') !== 'active') {
            return ApiResponse::fail('UNAUTHENTICATED', 'User inactive.', 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
