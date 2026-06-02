<?php

namespace App\Modules\Policies\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyAcknowledgement;
use Illuminate\View\View;

class MyPolicyController extends Controller
{
    /** Policies targeted at the current user's role(s), in their school. */
    public function index(): View
    {
        $user = auth()->user();
        $roles = $user->roles->pluck('slug')->all();

        $policies = Policy::query()
            ->where(function ($w) use ($user) {
                $w->whereNull('school_id');
                if ($user->school_id) {
                    $w->orWhere('school_id', $user->school_id);
                }
            })
            ->where(function ($w) use ($roles) {
                foreach ($roles as $slug) {
                    $w->orWhereJsonContains('target_roles', $slug);
                }
            })
            ->orderByDesc('id')
            ->get();

        $readIds = PolicyAcknowledgement::where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->pluck('policy_id')->all();

        return view('user.policies.index', [
            'policies' => $policies,
            'readIds' => array_fill_keys($readIds, true),
        ]);
    }

    /** Open a policy + record acknowledgement (card #105). */
    public function show(int $id): View
    {
        $user = auth()->user();
        $roles = $user->roles->pluck('slug')->all();

        $policy = Policy::query()
            ->where(function ($w) use ($user) {
                $w->whereNull('school_id');
                if ($user->school_id) {
                    $w->orWhere('school_id', $user->school_id);
                }
            })
            ->where(function ($w) use ($roles) {
                foreach ($roles as $slug) {
                    $w->orWhereJsonContains('target_roles', $slug);
                }
            })
            ->whereKey($id)
            ->firstOrFail();

        // Record the acknowledgement once — keep the FIRST read date.
        $ack = PolicyAcknowledgement::firstOrNew(
            ['policy_id' => $policy->id, 'user_id' => $user->id],
        );
        if (! $ack->read_at) {
            $ack->read_at = now();
            $ack->save();
        }

        return view('user.policies.show', ['policy' => $policy]);
    }
}
