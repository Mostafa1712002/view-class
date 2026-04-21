<?php

namespace App\Modules\Profile\Actions;

use App\Models\User;
use App\Modules\Profile\DTOs\UpdateProfileDto;
use App\Modules\Profile\Repositories\Contracts\ProfileRepository;

final class UpdateProfileAction
{
    public function __construct(private ProfileRepository $profiles) {}

    public function execute(User $user, UpdateProfileDto $dto): User
    {
        return $this->profiles->update($user, $dto->toArray());
    }
}
