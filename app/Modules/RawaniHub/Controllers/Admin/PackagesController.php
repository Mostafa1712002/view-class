<?php

namespace App\Modules\RawaniHub\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\CreatePackage;
use App\Modules\RawaniHub\Actions\DeletePackage;
use App\Modules\RawaniHub\Actions\TogglePackage;
use App\Modules\RawaniHub\Actions\UpdatePackage;
use App\Modules\RawaniHub\Http\Requests\PackageRequest;
use App\Modules\RawaniHub\Models\HubPackage;
use App\Modules\RawaniHub\Repositories\Contracts\PackageRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use Illuminate\Http\Request;

class PackagesController extends Controller
{
    public function __construct(private PackageRepository $packages, private PlatformRepository $platforms) {}

    public function index()
    {
        return view('hub.admin.packages.index', ['packages' => $this->packages->paginate()]);
    }

    public function create()
    {
        return view('hub.admin.packages.form', [
            'package' => new HubPackage(['is_active' => true, 'duration_days' => 365, 'sort' => 0]),
            'platforms' => $this->platforms->all(), 'selected' => [],
        ]);
    }

    public function store(PackageRequest $request, CreatePackage $create)
    {
        $create->execute($request->user(), $request->toData());

        return redirect()->route('admin.hub.packages.index')->with('success', __('hub_admin.saved'));
    }

    public function edit(int $package)
    {
        $package = $this->packages->find($package) ?? abort(404);

        return view('hub.admin.packages.form', [
            'package' => $package, 'platforms' => $this->platforms->all(),
            'selected' => $package->items->map(fn ($i) => $i->platform_id.'|'.$i->product_code)->all(),
        ]);
    }

    public function update(PackageRequest $request, int $package, UpdatePackage $update)
    {
        $update->execute($request->user(), $this->packages->find($package) ?? abort(404), $request->toData());

        return redirect()->route('admin.hub.packages.index')->with('success', __('hub_admin.saved'));
    }

    public function toggle(Request $request, int $package, TogglePackage $toggle)
    {
        $toggle->execute($request->user(), $this->packages->find($package) ?? abort(404));

        return back()->with('success', __('hub_admin.saved'));
    }

    public function destroy(Request $request, int $package, DeletePackage $delete)
    {
        $delete->execute($request->user(), $this->packages->find($package) ?? abort(404));

        return redirect()->route('admin.hub.packages.index')->with('success', __('hub_admin.deleted'));
    }
}
