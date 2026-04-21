<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DTOs\LoginDto;
use App\Modules\Auth\DTOs\TokenPair;
use App\Modules\Auth\Exceptions\AccountDisabledException;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Repositories\Contracts\UserRepository;
use Illuminate\Support\Facades\Hash;

final class LoginAction
{
    public function __construct(
        private UserRepository $users,
        private IssueTokenPairAction $issueTokens,
    ) {}

    public function execute(LoginDto $dto): TokenPair
    {
        $user = $this->users->findByUsernameOrEmail($dto->username);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (! ($user->is_active ?? true) || ($user->status ?? 'active') !== 'active') {
            throw new AccountDisabledException();
        }

        $this->users->recordLogin($user);

        return $this->issueTokens->execute($user, $dto->userAgent, $dto->ipAddress);
    }
}
