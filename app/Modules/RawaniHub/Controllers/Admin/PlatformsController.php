<?php

namespace App\Modules\RawaniHub\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\GeneratePlatformSecret;
use App\Modules\RawaniHub\Actions\SavePlatform;
use App\Modules\RawaniHub\Actions\TestPlatformConnection;
use App\Modules\RawaniHub\Http\Requests\PlatformRequest;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use Illuminate\Http\Request;

class PlatformsController extends Controller
{
    public function __construct(private PlatformRepository $platforms) {}

    public function index()
    {
        return view('hub.admin.platforms.index', ['platforms' => $this->platforms->all()]);
    }

    public function create()
    {
        return view('hub.admin.platforms.form', ['platform' => new HubPlatform(['is_enabled' => false])]);
    }

    public function store(PlatformRequest $request, SavePlatform $save)
    {
        $platform = $save->execute($request->user(), $request->attributes_());

        return redirect()->route('admin.hub.platforms.edit', $platform->id)->with('success', __('hub_admin.saved'));
    }

    public function edit(int $platform)
    {
        return view('hub.admin.platforms.form', ['platform' => $this->platforms->find($platform) ?? abort(404)]);
    }

    public function update(PlatformRequest $request, int $platform, SavePlatform $save)
    {
        $save->execute($request->user(), $request->attributes_(), $this->platforms->find($platform) ?? abort(404));

        return redirect()->route('admin.hub.platforms.edit', $platform)->with('success', __('hub_admin.saved'));
    }

    public function test(Request $request, int $platform, TestPlatformConnection $test)
    {
        $result = $test->execute($request->user(), $this->platforms->find($platform) ?? abort(404));

        return back()->with($result->ok() ? 'success' : 'error', $result->ok()
            ? __('hub_admin.test_ok')
            : __('hub_admin.test_failed', ['code' => $result->code ?? $result->status]));
    }

    public function secret(Request $request, int $platform, GeneratePlatformSecret $generate)
    {
        $secret = $generate->execute($request->user(), $this->platforms->find($platform) ?? abort(404));

        // shown once: flashed to the next request only
        return redirect()->route('admin.hub.platforms.edit', $platform)->with('new_secret', $secret);
    }
}
