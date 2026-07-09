<?php

namespace App\Modules\Behavior\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BehaviorRecord;
use App\Models\User;
use Illuminate\View\View;

class MyBehaviorController extends Controller
{
    /**
     * The signed-in user's own behaviour points (student) or their linked
     * children's points (parent). Anyone else sees an empty page.
     */
    public function index(): View
    {
        $user = auth()->user();

        if ($user->isStudent()) {
            $subjects = collect([$user]);
        } elseif ($user->isParent()) {
            $subjects = $user->children()->orderBy('name')->get();
        } else {
            $subjects = collect();
        }

        $subjects->each(function (User $s) {
            $s->behaviorRecords = BehaviorRecord::with(['behavior', 'action'])
                ->where('scope', 'student')
                ->where('subject_user_id', $s->id)
                ->orderByDesc('id')
                ->limit(200)
                ->get();
            // Separate sum so the total is correct even past the 200-row display cap.
            $s->pointsTotal = (int) BehaviorRecord::where('scope', 'student')
                ->where('subject_user_id', $s->id)
                ->sum('points');
        });

        return view('user.behavior.index', compact('subjects'));
    }
}
