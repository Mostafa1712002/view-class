<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DTOs\TokenPair;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Repositories\Contracts\SessionRepository;
use App\Modules\Auth\Repositories\Contracts\UserRepository;
use App\Modules\Auth\Services\JwtService;

final class RefreshTokenAction
{
    public function __construct(
        private JwtService $jwt,
        private SessionRepository $sessions,
        private UserRepository $users,
        private IssueTokenPairAction $issueTokens,
    ) {}

    public function execute(string $refreshToken, string $userAgent, string $ipAddress): TokenPair
    {
        try {
            $claims = $this->jwt->decode($refreshToken);
        } catch (\Throwable) {
            throw new InvalidCredentialsException('Invalid refresh token.');
        }

        if (($claims['typ'] ?? null) !== 'refresh' || ! isset($claims['sub'])) {
            throw new InvalidCredentialsException('Invalid refresh token.');
        }

        $session = $this->sessions->findActiveByHash(hash('sha256', $refreshToken));
        if (! $session) {
            throw new InvalidCredentialsException('Refresh token has been revoked.');
        }

        $user = $session->user;
        if (! $user) {
            throw new InvalidCredentialsException('User not found.');
        }

        // rotation — revoke old, issue new
        $this->sessions->revoke($session);

        return $this->issueTokens->execute($user, $userAgent, $ipAddress);
    }
}
