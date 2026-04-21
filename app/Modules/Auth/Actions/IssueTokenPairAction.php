<?php

namespace App\Modules\Auth\Actions;

use App\Models\User;
use App\Modules\Auth\DTOs\TokenPair;
use App\Modules\Auth\Repositories\Contracts\SessionRepository;
use App\Modules\Auth\Services\JwtService;
use Illuminate\Support\Str;

final class IssueTokenPairAction
{
    public const ACCESS_TTL = 900;      // 15 minutes
    public const REFRESH_TTL = 604800;  // 7 days

    public function __construct(
        private JwtService $jwt,
        private SessionRepository $sessions,
    ) {}

    public function execute(User $user, string $userAgent, string $ipAddress): TokenPair
    {
        $accessToken = $this->jwt->encode(
            ['sub' => $user->id, 'typ' => 'access'],
            self::ACCESS_TTL,
        );

        $refreshRandom = Str::random(64);
        $refreshToken = $this->jwt->encode(
            ['sub' => $user->id, 'typ' => 'refresh', 'jti' => $refreshRandom],
            self::REFRESH_TTL,
        );

        $this->sessions->create(
            $user->id,
            hash('sha256', $refreshToken),
            $userAgent,
            $ipAddress,
            self::REFRESH_TTL,
        );

        return new TokenPair(
            $accessToken,
            $refreshToken,
            self::ACCESS_TTL,
            self::REFRESH_TTL,
            $user,
        );
    }
}
