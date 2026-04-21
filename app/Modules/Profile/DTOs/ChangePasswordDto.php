<?php

namespace App\Modules\Profile\DTOs;

final readonly class ChangePasswordDto
{
    public function __construct(
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
