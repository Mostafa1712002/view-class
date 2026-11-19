<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\User;
use App\Modules\RawaniHub\DTOs\PlatformResult;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Services\PlatformClient;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;

final class TestPlatformConnection
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformRepository $platforms) {}

    public function execute(User $actor, HubPlatform $platform): PlatformResult
    {
        $this->authorizeHubAdmin($actor);
        if (! $platform->secret) {
            $result = new PlatformResult(PlatformResult::AUTH, 0, code: 'NO_SECRET', message: 'no secret');
        } else {
            $result = (new PlatformClient($platform->base_url, $platform->secret))->ping();
        }
        $this->platforms->update($platform, $result->ok()
            ? ['last_ok_at' => now(), 'last_error' => null]
            : ['last_error' => mb_substr(($result->code ?? $result->status).': '.($result->message ?? ''), 0, 500)]);

        return $result;
    }
}
