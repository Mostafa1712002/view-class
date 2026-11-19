<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;

/** Soft-deletes a package; subscriptions keep their item/price snapshot and still resolve it withTrashed(). */
final class DeletePackage
{
    use RequiresSuperAdmin;

    public function __construct(private PackageRepository $packages) {}

    public function execute(User $actor, HubPackage $package): void
    {
        $this->authorizeHubAdmin($actor);

        DB::transaction(function () use ($package) {
            $old = $package->toArray();
            $this->packages->delete($package);
            ActivityLog::log('hub.package.delete', "حذف باقة: {$package->name_ar}", $package, $old, null);
        });
    }
}
