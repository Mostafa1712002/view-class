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
    /**
     * GET confirmation page. Hitting POST-only /impersonate via a bare URL
     * yields a 405/419; this presents a tiny CSRF-protected confirm form.
     */
    /**
     * Privilege guard: a non-super-admin viewer may only view-login as a
     * non-admin user within their OWN school. Prevents privilege escalation
     * (viewing into a super-admin / school-admin / another school's account).
     */
    private function assertCanViewTarget(User $me, User $target): void
    {
        if ($me->isSuperAdmin()) {
            return;
        }
        abort_if(
            $target->isSuperAdmin()
                || $target->isSchoolAdmin()
                || (int) $target->school_id !== (int) $me->school_id,
            403,
            'لا يمكنك الدخول للإطلاع على هذا الحساب.'
        );
    }

    public function confirm(Request $request, int $id): \Illuminate\Contracts\View\View|RedirectResponse
    {
        $me = Auth::user();
        abort_unless($me && ($me->isSuperAdmin() || $me->canDo('viewing.login_as_user')), 403);

        $target = User::query()->whereKey($id)->first();
        if (!$target) {
            return redirect()->route('dashboard')->with('error', __('users.not_found'));
        }
        $this->assertCanViewTarget($me, $target);

        return view('admin.users.impersonate-confirm', compact('target'));
    }

    public function start(Request $request, int $id): RedirectResponse
    {
        $me = Auth::user();
        abort_unless($me && ($me->isSuperAdmin() || $me->canDo('viewing.login_as_user')), 403);

        $target = User::query()->whereKey($id)->first();
        if (!$target) {
            return back()->with('error', __('users.not_found'));
        }
        if ($target->id === $me->id) {
            return back();
        }
        $this->assertCanViewTarget($me, $target);

        $actorRole  = $this->primaryRoleLabel($me);
        $targetRole = $this->primaryRoleLabel($target);

        DB::table('activity_logs')->insert([
            'user_id'     => $me->id,
            'action'      => 'impersonate.start',
            'model_type'  => User::class,
            'model_id'    => $target->id,
            'description' => "دخول للإطلاع: {$me->username} ({$actorRole}) اطلع على حساب #{$target->id} {$target->username} ({$targetRole})",
            'ip_address'  => $request->ip(),
            'user_agent'  => substr((string) $request->userAgent(), 0, 255),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $request->session()->put('impersonator_id', $me->id);
        $request->session()->put('impersonator_role', $actorRole);
        $request->session()->put('impersonating_target_name', $target->name);
        $request->session()->put('impersonating_target_role', $targetRole);
        Auth::loginUsingId($target->id);

        return redirect()->route('dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        if (!$impersonatorId) {
            return redirect()->route('dashboard');
        }

        $targetId   = Auth::id();
        $targetUser = User::query()->whereKey($targetId)->first();
        $actorRole  = $request->session()->pull('impersonator_role', '');
        $targetRole = $request->session()->pull('impersonating_target_role', '');
        $targetName = $request->session()->pull('impersonating_target_name', $targetUser?->username ?? '');

        DB::table('activity_logs')->insert([
            'user_id'     => $impersonatorId,
            'action'      => 'impersonate.stop',
            'model_type'  => User::class,
            'model_id'    => $targetId,
            'description' => "إنهاء الإطلاع: عاد المستخدم ({$actorRole}) من حساب #{$targetId} {$targetName} ({$targetRole})",
            'ip_address'  => $request->ip(),
            'user_agent'  => substr((string) $request->userAgent(), 0, 255),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        Auth::loginUsingId($impersonatorId);
        return redirect()->route('admin.users.students.index');
    }

    /**
     * Return the primary Arabic role label for the given user (for activity log readability).
     */
    private function primaryRoleLabel(User $user): string
    {
        if ($user->isSuperAdmin())  return 'مدير النظام';
        if ($user->isSchoolAdmin()) return 'مدير مدرسة';
        if ($user->isTeacher())     return 'معلم';
        if ($user->isParent())      return 'ولي أمر';
        if ($user->isStudent())     return 'طالب';
        $first = $user->roles()->first();
        return $first?->name ?? 'مستخدم';
    }
}
