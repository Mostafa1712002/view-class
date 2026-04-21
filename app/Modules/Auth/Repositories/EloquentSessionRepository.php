<?php

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Models\AuthSession;
use App\Modules\Auth\Repositories\Contracts\SessionRepository;
use Illuminate\Support\Carbon;

final class EloquentSessionRepository implements SessionRepository
{
    public function create(int $userId, string $refreshTokenHash, string $userAgent, string $ipAddress, int $ttlSeconds): AuthSession
    {
        return AuthSession::create([
            'user_id' => $userId,
            'refresh_token_hash' => $refreshTokenHash,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'ip_address' => $ipAddress,
            'expires_at' => Carbon::now()->addSeconds($ttlSeconds),
        ]);
    }

    public function findActiveByHash(string $hash): ?AuthSession
    {
        return AuthSession::where('refresh_token_hash', $hash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    public function revoke(AuthSession $session): void
    {
        $session->forceFill(['revoked_at' => Carbon::now()])->save();
    }

    public function revokeAllForUser(int $userId): void
    {
        AuthSession::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Carbon::now()]);
    }
}
