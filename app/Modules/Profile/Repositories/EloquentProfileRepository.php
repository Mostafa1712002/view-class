<?php

namespace App\Modules\Profile\Repositories;

use App\Models\User;
use App\Modules\Profile\Repositories\Contracts\ProfileRepository;
use Illuminate\Support\Facades\Hash;

final class EloquentProfileRepository implements ProfileRepository
{
    public function update(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();
        return $user->fresh();
    }

    public function setPassword(User $user, string $newPassword): void
    {
        $user->forceFill(['password' => Hash::make($newPassword)])->save();
    }

    public function setProfilePicture(User $user, string $path): User
    {
        $user->forceFill(['profile_picture' => $path])->save();
        return $user->fresh();
    }
}
