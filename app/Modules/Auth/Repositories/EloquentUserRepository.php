<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;
use App\Modules\Auth\Repositories\Contracts\UserRepository;

final class EloquentUserRepository implements UserRepository
{
    public function findByUsernameOrEmail(string $identifier): ?User
    {
        return User::where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();
    }

    public function recordLogin(User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();
    }
}
