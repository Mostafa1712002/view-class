<?php

namespace App\Modules\Auth\Repositories\Contracts;

use App\Models\User;

interface UserRepository
{
    public function findByUsernameOrEmail(string $identifier): ?User;

    public function recordLogin(User $user): void;
}
