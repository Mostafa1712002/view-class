<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;

/** Flips is_active. Existing subscriptions keep their snapshot; an inactive package can no longer be granted. */
final class TogglePackage
{
    use RequiresSuperAdmin;

    public function __construct(private PackageRepository $packages) {}

    public function execute(User $actor, HubPackage $package): HubPackage
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($package) {
            $old = ['is_active' => (bool) $package->is_active];
            $package = $this->packages->update($package, ['is_active' => ! $package->is_active]);
            ActivityLog::log('hub.package.toggle', "تبديل حالة باقة: {$package->name_ar}", $package, $old, ['is_active' => (bool) $package->is_active]);

            return $package;
        });
    }
}
