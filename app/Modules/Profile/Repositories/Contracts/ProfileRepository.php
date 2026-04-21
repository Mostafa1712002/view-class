<?php

namespace App\Modules\Profile\Repositories\Contracts;

use App\Models\User;

interface ProfileRepository
{
    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User;

    public function setPassword(User $user, string $newPassword): void;

    public function setProfilePicture(User $user, string $path): User;
}
