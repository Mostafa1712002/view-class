<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;

/** Creates or edits a platform row. The secret is never set here (see GeneratePlatformSecret). */
final class SavePlatform
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformRepository $platforms) {}

    public function execute(User $actor, array $attributes, ?HubPlatform $platform = null): HubPlatform
    {
        $this->authorizeHubAdmin($actor);
        unset($attributes['secret']);
        $old = $platform?->toArray();
        $platform = $platform ? $this->platforms->update($platform, $attributes) : $this->platforms->create($attributes);
        ActivityLog::log($old ? 'hub.platform.update' : 'hub.platform.create', "حفظ منصة: {$platform->name_ar}", $platform, $old, $platform->toArray());

        return $platform;
    }
}
