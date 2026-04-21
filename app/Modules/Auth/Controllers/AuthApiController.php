<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Actions\LogoutAction;
use App\Modules\Auth\Actions\RefreshTokenAction;
use App\Modules\Auth\DTOs\LoginDto;
use App\Modules\Auth\DTOs\TokenPair;
use App\Modules\Auth\Exceptions\AccountDisabledException;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RefreshRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthApiController extends Controller
{
    public function login(
        LoginRequest $request,
        LoginAction $login,
    ): JsonResponse {
        $dto = new LoginDto(
            username: $request->string('username')->toString(),
            password: $request->string('password')->toString(),
            userAgent: (string) $request->userAgent(),
            ipAddress: (string) $request->ip(),
        );

        try {
            $tokens = $login->execute($dto);
        } catch (InvalidCredentialsException) {
            return ApiResponse::fail('INVALID_CREDENTIALS', trans('auth.invalid_credentials'), 401);
        } catch (AccountDisabledException) {
            return ApiResponse::fail('ACCOUNT_DISABLED', trans('auth.account_disabled'), 403);
        }

        return ApiResponse::ok($this->tokenPayload($tokens));
    }

    public function refresh(
        RefreshRequest $request,
        RefreshTokenAction $refresh,
    ): JsonResponse {
        try {
            $tokens = $refresh->execute(
                $request->string('refreshToken')->toString(),
                (string) $request->userAgent(),
                (string) $request->ip(),
            );
        } catch (InvalidCredentialsException $e) {
            return ApiResponse::fail('INVALID_REFRESH_TOKEN', $e->getMessage() ?: 'Invalid refresh token.', 401);
        }

        return ApiResponse::ok($this->tokenPayload($tokens));
    }

    public function logout(Request $request, LogoutAction $logout): JsonResponse
    {
        $logout->execute($request->input('refreshToken'));

        return ApiResponse::ok(null, 'Logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::fail('UNAUTHENTICATED', 'Unauthenticated.', 401);
        }

        return ApiResponse::ok(['user' => UserResource::toArray($user)]);
    }

    private function tokenPayload(TokenPair $tokens): array
    {
        return [
            'accessToken' => $tokens->accessToken,
            'refreshToken' => $tokens->refreshToken,
            'accessTokenExpiresIn' => $tokens->accessTtlSeconds,
            'refreshTokenExpiresIn' => $tokens->refreshTtlSeconds,
            'user' => UserResource::toArray($tokens->user),
        ];
    }
}
