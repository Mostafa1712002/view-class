<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Str;

/** Generates a new shared secret; the plain value is returned once and never stored in clear or logged. */
final class GeneratePlatformSecret
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformRepository $platforms) {}

    public function execute(User $actor, HubPlatform $platform): string
    {
        $this->authorizeHubAdmin($actor);
        $secret = Str::random(64);
        $this->platforms->update($platform, ['secret' => $secret]);
        ActivityLog::log('hub.platform.secret', "توليد سر جديد للمنصة: {$platform->name_ar}", $platform);

        return $secret;
    }
}
