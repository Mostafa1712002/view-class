<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImpersonateController extends Controller
{
    public function start(Request $request, int $id): RedirectResponse
    {
        $me = Auth::user();
        abort_unless($me && $me->isSuperAdmin(), 403);

        $target = User::query()->whereKey($id)->first();
        if (!$target) {
            return back()->with('error', __('users.not_found'));
        }
        if ($target->id === $me->id) {
            return back();
        }

        DB::table('activity_logs')->insert([
            'user_id' => $me->id,
            'action' => 'impersonate.start',
            'model_type' => User::class,
            'model_id' => $target->id,
            'description' => "Impersonating user #{$target->id} ({$target->username})",
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request->session()->put('impersonator_id', $me->id);
        Auth::loginUsingId($target->id);

        return redirect()->route('dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        if (!$impersonatorId) {
            return redirect()->route('dashboard');
        }
        DB::table('activity_logs')->insert([
            'user_id' => $impersonatorId,
            'action' => 'impersonate.stop',
            'model_type' => User::class,
            'model_id' => Auth::id(),
            'description' => 'Stopped impersonating',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Auth::loginUsingId($impersonatorId);
        return redirect()->route('admin.users.students.index');
    }
}
