<?php

namespace App\Modules\Auth\DTOs;

final readonly class LoginDto
{
    public function __construct(
        public string $username,
        public string $password,
        public string $userAgent,
        public string $ipAddress,
    ) {}
}
