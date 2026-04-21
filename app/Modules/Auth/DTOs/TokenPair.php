<?php

namespace App\Modules\Auth\DTOs;

use App\Models\User;

final readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $accessTtlSeconds,
        public int $refreshTtlSeconds,
        public User $user,
    ) {}
}
