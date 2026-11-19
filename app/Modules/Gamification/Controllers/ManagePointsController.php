<?php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BehaviorRecord;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Modules\Gamification\Actions\AdjustPoints;
use App\Modules\Gamification\Http\Requests\AdjustPointsRequest;
use App\Modules\Gamification\Models\PointTransaction;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ManagePointsController extends Controller
{
    use HasSchoolScope;

    public function __construct(private PointLedgerRepository $ledger) {}

    public function index(Request $request)
    {
        $schoolId = $this->scopedSchoolId();
        $f = $request->validate([
            'type' => ['nullable', 'in:student,teacher,vice'],
            'section_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);
        $type = $f['type'] ?? 'student';

        // Filter ids must belong to the active school; anything else is dropped (no cross-school class names).
        $sections = Section::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name']);
        if (! $sections->contains('id', $f['section_id'] ?? null)) {
            unset($f['section_id']);
        }
        $classes = ClassRoom::whereIn('section_id', isset($f['section_id']) ? [$f['section_id']] : $sections->pluck('id'))
            ->orderBy('name')->get(['id', 'name', 'division']);
        if (! $classes->contains('id', $f['class_id'] ?? null)) {
            unset($f['class_id']);
        }

        $rows = $this->ledger->balances($schoolId, array_merge($f, ['type' => $type, 'month' => now()->format('Y-m')]))->withQueryString();

        // Behaviour points: separate, read-only (students and teachers only).
        $behaviour = $type === 'vice' ? collect() : BehaviorRecord::where('scope', $type)
            ->whereIn('subject_user_id', $rows->pluck('id'))->groupBy('subject_user_id')
            ->selectRaw('subject_user_id, SUM(points) as total')->pluck('total', 'subject_user_id');

        return view('points.manage.index', compact('rows', 'behaviour', 'sections', 'classes', 'type'));
    }

    public function show(Request $request, int $user)
    {
        $person = $this->person($user);
        $history = $this->ledger->history($person->id);
        $balance = $this->ledger->balance($person->id);
        $behaviourTotal = $person->isTeacher() || $person->isStudent()
            ? (int) BehaviorRecord::where('scope', $person->isStudent() ? 'student' : 'teacher')->where('subject_user_id', $person->id)->sum('points')
            : null;
        $canAdjust = Gate::allows('adjust', [PointTransaction::class, $person]);

        return view('points.manage.show', compact('person', 'history', 'balance', 'behaviourTotal', 'canAdjust'));
    }

    public function adjust(AdjustPointsRequest $request, int $user, AdjustPoints $action)
    {
        $person = $this->person($user);
        Gate::authorize('adjust', [PointTransaction::class, $person]);
        $action->execute($person, (int) $request->validated('points'), $request->validated('reason'), $request->user()->id);

        return redirect()->route('manage.points.show', $person->id)->with('success', __('points_manage.adjusted'));
    }

    /** Scoped lookup: a user outside the active school is a 404. */
    private function person(int $id)
    {
        $schoolId = $this->scopedSchoolId();
        $person = $this->ledger->findUser($id);
        abort_if(! $person || ($schoolId !== null && (int) $person->school_id !== $schoolId), 404);

        return $person;
    }
}
