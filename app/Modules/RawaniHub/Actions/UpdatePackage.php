<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\DTOs\PackageData;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;

/** Existing subscriptions keep their item/price snapshot, so edits only affect future grants and purchases. */
final class UpdatePackage
{
    use PackageRules, RequiresSuperAdmin;

    public function __construct(private PackageRepository $packages, private PlatformRepository $platforms) {}

    public function execute(User $actor, HubPackage $package, PackageData $data): HubPackage
    {
        $this->authorizeHubAdmin($actor);
        $this->validatePackage($data, $this->platforms);

        return DB::transaction(function () use ($actor, $package, $data) {
            $old = $this->packages->find($package->id)->toArray();
            $this->packages->update($package, $this->packageAttributes($data));
            $this->packages->syncItems($package, $data->items);
            $package = $this->packages->find($package->id);
            ActivityLog::log('hub.package.update', "تعديل باقة: {$package->name_ar}", $package, $old, $package->toArray());

            return $package;
        });
    }
}
