<?php

namespace App\Modules\Behavior\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Behavior;
use App\Models\BehaviorGroup;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BehaviorController extends Controller
{
    use HasSchoolScope;

    private function tab(Request $request): string
    {
        $tab = (string) $request->get('tab', 'student');

        return in_array($tab, BehaviorGroup::SCOPES, true) ? $tab : 'student';
    }

    /** Active groups of a scope, for the behaviour form's group select. */
    private function groupsFor(string $tab)
    {
        $schoolId = $this->activeSchoolId();

        return BehaviorGroup::query()
            ->where('scope', $tab)
            ->where('is_active', true)
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->orderBy('name')
            ->get(['id', 'name', 'type']);
    }

    public function index(Request $request): View
    {
        $tab = $this->tab($request);
        $schoolId = $this->activeSchoolId();
        $q = trim((string) $request->get('q', ''));

        $behaviors = Behavior::query()
            ->with('group')
            ->withCount('actions')
            ->whereHas('group', fn ($g) => $g->where('scope', $tab))
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->when($q !== '', fn ($w) => $w->where('name', 'like', '%'.$q.'%'))
            ->orderByDesc('id')
            ->get();

        return view('admin.behavior.behaviors.index', compact('behaviors', 'tab', 'q'));
    }

    public function create(Request $request): View
    {
        $tab = $this->tab($request);

        return view('admin.behavior.behaviors.create', [
            'behavior' => new Behavior(['is_active' => true]),
            'tab' => $tab,
            'groups' => $this->groupsFor($tab),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tab = $this->tab($request);
        $data = $this->validateBehavior($request, $tab);

        Behavior::create([
            'behavior_group_id' => $data['behavior_group_id'],
            'school_id' => $this->activeSchoolId(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.behavior.behaviors.index', ['tab' => $tab])
            ->with('status', __('behavior.flash.behavior_created'));
    }

    public function edit(int $id): View
    {
        $behavior = $this->findScoped($id);
        $tab = $behavior->group->scope ?? 'student';

        return view('admin.behavior.behaviors.edit', [
            'behavior' => $behavior,
            'tab' => $tab,
            'groups' => $this->groupsFor($tab),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $behavior = $this->findScoped($id);
        $tab = $behavior->group->scope ?? 'student';
        $data = $this->validateBehavior($request, $tab);

        $behavior->update([
            'behavior_group_id' => $data['behavior_group_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('admin.behavior.behaviors.index', ['tab' => $tab])
            ->with('status', __('behavior.flash.behavior_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $behavior = $this->findScoped($id);
        $tab = $behavior->group->scope ?? 'student';

        if ($behavior->actions()->exists()) {
            return back()->with('error', __('behavior.flash.behavior_has_actions'));
        }

        $behavior->delete();

        return redirect()->route('admin.behavior.behaviors.index', ['tab' => $tab])
            ->with('status', __('behavior.flash.behavior_deleted'));
    }

    public function toggle(int $id): RedirectResponse
    {
        $behavior = $this->findScoped($id);
        $behavior->update(['is_active' => ! $behavior->is_active]);

        return back()->with('status', __('behavior.flash.behavior_updated'));
    }

    private function findScoped(int $id): Behavior
    {
        $schoolId = $this->activeSchoolId();

        return Behavior::query()
            ->with('group')
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->whereKey($id)
            ->firstOrFail();
    }

    private function validateBehavior(Request $request, string $tab): array
    {
        $schoolId = $this->activeSchoolId();

        return $request->validate([
            // The group must exist, be active, and belong to the current scope/school.
            'behavior_group_id' => [
                'required',
                Rule::exists('behavior_groups', 'id')->where(function ($q) use ($tab, $schoolId) {
                    $q->where('scope', $tab)->where('is_active', true)->whereNull('deleted_at');
                    if ($schoolId) {
                        $q->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id'));
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
