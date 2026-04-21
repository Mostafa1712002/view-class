<?php

namespace App\Modules\Auth\Repositories\Contracts;

use App\Modules\Auth\Models\AuthSession;

interface SessionRepository
{
    public function create(int $userId, string $refreshTokenHash, string $userAgent, string $ipAddress, int $ttlSeconds): AuthSession;

    public function findActiveByHash(string $hash): ?AuthSession;

    public function revoke(AuthSession $session): void;

    public function revokeAllForUser(int $userId): void;
}
