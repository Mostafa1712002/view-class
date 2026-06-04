<?php

namespace App\Modules\Behavior\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Behavior;
use App\Models\BehaviorAction;
use App\Models\BehaviorGroup;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BehaviorActionController extends Controller
{
    use HasSchoolScope;

    private function tab(Request $request): string
    {
        $tab = (string) $request->get('tab', 'student');

        return in_array($tab, BehaviorGroup::SCOPES, true) ? $tab : 'student';
    }

    /** Active behaviours of a scope, for the action form's behaviour select. */
    private function behaviorsFor(string $tab)
    {
        $schoolId = $this->activeSchoolId();

        return Behavior::query()
            ->where('is_active', true)
            ->whereHas('group', fn ($g) => $g->where('scope', $tab))
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function index(Request $request): View
    {
        $tab = $this->tab($request);
        $schoolId = $this->activeSchoolId();
        $q = trim((string) $request->get('q', ''));

        $actions = BehaviorAction::query()
            ->with('behavior')
            ->whereHas('behavior.group', fn ($g) => $g->where('scope', $tab))
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->when($q !== '', fn ($w) => $w->where('description', 'like', '%'.$q.'%'))
            ->orderByDesc('id')
            ->get();

        return view('admin.behavior.actions.index', compact('actions', 'tab', 'q'));
    }

    public function create(Request $request): View
    {
        $tab = $this->tab($request);

        return view('admin.behavior.actions.create', [
            'action' => new BehaviorAction(['points' => 0, 'point_type' => 'add', 'is_active' => true]),
            'tab' => $tab,
            'behaviors' => $this->behaviorsFor($tab),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tab = $this->tab($request);
        $data = $this->validateAction($request, $tab);

        BehaviorAction::create([
            'behavior_id' => $data['behavior_id'],
            'school_id' => $this->activeSchoolId(),
            'description' => $data['description'],
            'points' => (int) ($data['points'] ?? 0),
            'point_type' => $data['point_type'],
            'notify_parent' => (bool) ($data['notify_parent'] ?? false),
            'needs_followup' => (bool) ($data['needs_followup'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.behavior.actions.index', ['tab' => $tab])
            ->with('status', __('behavior.flash.action_created'));
    }

    public function edit(int $id): View
    {
        $action = $this->findScoped($id);
        $tab = $action->behavior->group->scope ?? 'student';

        return view('admin.behavior.actions.edit', [
            'action' => $action,
            'tab' => $tab,
            'behaviors' => $this->behaviorsFor($tab),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $action = $this->findScoped($id);
        $tab = $action->behavior->group->scope ?? 'student';
        $data = $this->validateAction($request, $tab);

        $action->update([
            'behavior_id' => $data['behavior_id'],
            'description' => $data['description'],
            'points' => (int) ($data['points'] ?? 0),
            'point_type' => $data['point_type'],
            'notify_parent' => (bool) ($data['notify_parent'] ?? false),
            'needs_followup' => (bool) ($data['needs_followup'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('admin.behavior.actions.index', ['tab' => $tab])
            ->with('status', __('behavior.flash.action_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $action = $this->findScoped($id);
        $tab = $action->behavior->group->scope ?? 'student';
        $action->delete();

        return redirect()->route('admin.behavior.actions.index', ['tab' => $tab])
            ->with('status', __('behavior.flash.action_deleted'));
    }

    public function toggle(int $id): RedirectResponse
    {
        $action = $this->findScoped($id);
        $action->update(['is_active' => ! $action->is_active]);

        return back()->with('status', __('behavior.flash.action_updated'));
    }

    private function findScoped(int $id): BehaviorAction
    {
        $schoolId = $this->activeSchoolId();

        return BehaviorAction::query()
            ->with('behavior.group')
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->whereKey($id)
            ->firstOrFail();
    }

    private function validateAction(Request $request, string $tab): array
    {
        $schoolId = $this->activeSchoolId();

        return $request->validate([
            // Behaviour must exist, be active, and belong to the current scope/school.
            'behavior_id' => [
                'required',
                Rule::exists('behaviors', 'id')->where(function ($q) use ($schoolId) {
                    $q->where('is_active', true)->whereNull('deleted_at');
                    if ($schoolId) {
                        $q->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id'));
                    }
                }),
            ],
            'description' => ['required', 'string', 'max:2000'],
            'points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'point_type' => ['required', Rule::in(BehaviorAction::POINT_TYPES)],
            'notify_parent' => ['nullable', 'boolean'],
            'needs_followup' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
