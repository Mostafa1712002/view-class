<?php

namespace App\Modules\Behavior\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BehaviorGroup;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BehaviorGroupController extends Controller
{
    use HasSchoolScope;

    /** Resolve the requested scope tab, defaulting to students. */
    private function tab(Request $request): string
    {
        $tab = (string) $request->get('tab', 'student');

        return in_array($tab, BehaviorGroup::SCOPES, true) ? $tab : 'student';
    }

    public function index(Request $request): View
    {
        $tab = $this->tab($request);
        $schoolId = $this->activeSchoolId();
        $q = trim((string) $request->get('q', ''));

        $groups = BehaviorGroup::query()
            ->where('scope', $tab)
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->when($q !== '', fn ($w) => $w->where('name', 'like', '%'.$q.'%'))
            ->orderByDesc('id')
            ->get();

        // Behaviours table arrives with card #115; until then every group has zero.
        $counts = Schema::hasTable('behaviors')
            ? DB::table('behaviors')->whereIn('behavior_group_id', $groups->pluck('id'))->whereNull('deleted_at')
                ->selectRaw('behavior_group_id, count(*) as c')->groupBy('behavior_group_id')->pluck('c', 'behavior_group_id')
            : collect();
        $groups->each(fn ($g) => $g->behaviors_count = (int) ($counts[$g->id] ?? 0));

        return view('admin.behavior.groups.index', compact('groups', 'tab', 'q'));
    }

    public function create(Request $request): View
    {
        $tab = $this->tab($request);
        $group = new BehaviorGroup(['scope' => $tab, 'type' => 'positive', 'available_for_teacher' => true, 'is_active' => true]);

        return view('admin.behavior.groups.create', compact('group', 'tab'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGroup($request);

        BehaviorGroup::create([
            'school_id' => $this->activeSchoolId(),
            'scope' => $data['scope'],
            'name' => $data['name'],
            'type' => $data['type'],
            'available_for_teacher' => (bool) ($data['available_for_teacher'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.behavior.groups.index', ['tab' => $data['scope']])
            ->with('status', __('behavior.flash.group_created'));
    }

    public function edit(int $id): View
    {
        $group = $this->findScoped($id);

        return view('admin.behavior.groups.edit', ['group' => $group, 'tab' => $group->scope]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $group = $this->findScoped($id);
        $data = $this->validateGroup($request, $group->id);

        $group->update([
            'scope' => $data['scope'],
            'name' => $data['name'],
            'type' => $data['type'],
            'available_for_teacher' => (bool) ($data['available_for_teacher'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()
            ->route('admin.behavior.groups.index', ['tab' => $group->scope])
            ->with('status', __('behavior.flash.group_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $group = $this->findScoped($id);

        // Business rule: a group with linked behaviours can't be deleted — disable it instead.
        if (Schema::hasTable('behaviors')
            && DB::table('behaviors')->where('behavior_group_id', $group->id)->whereNull('deleted_at')->exists()) {
            return back()->with('error', __('behavior.flash.group_has_behaviors'));
        }

        $scope = $group->scope;
        $group->delete();

        return redirect()
            ->route('admin.behavior.groups.index', ['tab' => $scope])
            ->with('status', __('behavior.flash.group_deleted'));
    }

    /** Toggle active state (disable instead of delete). */
    public function toggle(int $id): RedirectResponse
    {
        $group = $this->findScoped($id);
        $group->update(['is_active' => ! $group->is_active]);

        return back()->with('status', __('behavior.flash.group_updated'));
    }

    private function findScoped(int $id): BehaviorGroup
    {
        $schoolId = $this->activeSchoolId();

        return BehaviorGroup::query()
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->whereKey($id)
            ->firstOrFail();
    }

    private function validateGroup(Request $request, ?int $ignoreId = null): array
    {
        $schoolId = $this->activeSchoolId();

        return $request->validate([
            'scope' => ['required', Rule::in(BehaviorGroup::SCOPES)],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('behavior_groups', 'name')
                    ->where(fn ($q) => $q->where('scope', $request->input('scope'))->where('school_id', $schoolId)->whereNull('deleted_at'))
                    ->ignore($ignoreId),
            ],
            'type' => ['required', Rule::in(BehaviorGroup::TYPES)],
            'available_for_teacher' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
