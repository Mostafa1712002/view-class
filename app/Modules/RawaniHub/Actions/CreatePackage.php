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

final class CreatePackage
{
    use PackageRules, RequiresSuperAdmin;

    public function __construct(private PackageRepository $packages, private PlatformRepository $platforms) {}

    public function execute(User $actor, PackageData $data): HubPackage
    {
        $this->authorizeHubAdmin($actor);
        $this->validatePackage($data, $this->platforms);

        return DB::transaction(function () use ($actor, $data) {
            $package = $this->packages->create($this->packageAttributes($data) + ['created_by' => $actor->id]);
            $this->packages->syncItems($package, $data->items);
            $package = $this->packages->find($package->id);
            ActivityLog::log('hub.package.create', "إنشاء باقة: {$package->name_ar}", $package, null, $package->toArray());

            return $package;
        });
    }
}
