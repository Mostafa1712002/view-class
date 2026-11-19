<?php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BehaviorRecord;
use App\Models\User;
use App\Modules\Gamification\Models\PointReward;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use App\Modules\Gamification\Support\RuleCatalog;
use App\Modules\Gamification\Support\RuleSettings;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** «نقاطي» (student / teacher / وكيل) and «نقاط أبنائي» (parent, child switcher). Read-only. */
class MyPointsController extends Controller
{
    public function index(Request $request, PointLedgerRepository $ledger, RuleSettings $settings): View
    {
        $user = auth()->user();
        $children = collect();
        $subject = $user;

        if ($user->isParent()) {
            $children = $user->children()->orderBy('users.name')->get();
            $subject = null;
            if ($request->filled('child')) {
                // children() is the same rule as PointsPolicy::viewChild
                $subject = $children->firstWhere('id', (int) $request->query('child')) ?? abort(404);
            } else {
                $subject = $children->first();
            }
        }

        $data = ['user' => $user, 'subject' => $subject, 'children' => $children, 'isParent' => $user->isParent()];

        if ($subject) {
            $data += [
                'balance' => $ledger->balance($subject->id),
                'history' => $ledger->history($subject->id)->withQueryString(),
                'rewards' => PointReward::with('period')->where('user_id', $subject->id)->latest('id')->get(),
                'behaviorTotal' => (int) BehaviorRecord::where('scope', 'student')
                    ->where('subject_user_id', $subject->id)->sum('points'),
                'showBehavior' => $subject->isStudent(),
                'viceRules' => $subject->jobTitle?->slug === 'vice' ? $this->viceRules($subject, $settings) : [],
            ];
        }

        return view('points.my.index', $data);
    }

    private function viceRules(User $vice, RuleSettings $settings): array
    {
        return collect(RuleCatalog::forBeneficiary('vice'))
            ->map(fn ($meta, $key) => ['key' => $key] + $settings->effective($key, (int) $vice->school_id))
            ->filter(fn ($r) => $r['enabled'])
            ->values()->all();
    }
}
