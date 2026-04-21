<?php

namespace App\Modules\Profile\Actions;

use App\Models\User;
use App\Modules\Profile\DTOs\ChangePasswordDto;
use App\Modules\Profile\Exceptions\InvalidCurrentPasswordException;
use App\Modules\Profile\Exceptions\SamePasswordException;
use App\Modules\Profile\Repositories\Contracts\ProfileRepository;
use Illuminate\Support\Facades\Hash;

final class ChangePasswordAction
{
    public function __construct(private ProfileRepository $profiles) {}

    public function execute(User $user, ChangePasswordDto $dto): void
    {
        if (! Hash::check($dto->currentPassword, $user->password)) {
            throw new InvalidCurrentPasswordException();
        }

        if ($dto->currentPassword === $dto->newPassword) {
            throw new SamePasswordException();
        }

        $this->profiles->setPassword($user, $dto->newPassword);
    }
}
