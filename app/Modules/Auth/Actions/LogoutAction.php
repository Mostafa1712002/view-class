<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Repositories\Contracts\SessionRepository;
use App\Modules\Auth\Services\JwtService;

final class LogoutAction
{
    public function __construct(
        private JwtService $jwt,
        private SessionRepository $sessions,
    ) {}

    public function execute(?string $refreshToken): void
    {
        if (! $refreshToken) {
            return;
        }

        $hash = hash('sha256', $refreshToken);
        $session = $this->sessions->findActiveByHash($hash);
        if ($session) {
            $this->sessions->revoke($session);
        }
    }
}
