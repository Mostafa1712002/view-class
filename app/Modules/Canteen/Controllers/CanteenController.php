<?php

namespace App\Modules\Canteen\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\School;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CanteenController extends Controller
{
    use HasSchoolScope;

    /** Grade levels 1..12 for the target-grades picker. */
    private function gradeList(): array
    {
        $out = [];
        for ($i = 1; $i <= 12; $i++) {
            $out[$i] = __('canteen.grade', ['n' => $i]);
        }

        return $out;
    }

    private function schoolsForScope()
    {
        $schoolId = $this->activeSchoolId();

        return School::query()
            ->when($schoolId, fn ($w) => $w->where('id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Admins eligible to manage a canteen (card #116: "يُختار من الإداريين"). */
    private function managerCandidates(?int $schoolId)
    {
        return User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'school-admin'))
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);
    }

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $q = trim((string) $request->get('q', ''));

        $canteens = Canteen::query()
            ->with(['manager', 'school'])
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name_ar', 'like', '%'.$q.'%')->orWhere('name_en', 'like', '%'.$q.'%')))
            ->orderByDesc('id')
            ->get();

        // Counts via guarded DB queries (category/product tables arrive in the next slice).
        $ids = $canteens->pluck('id');
        $catCounts = \Illuminate\Support\Facades\Schema::hasTable('canteen_categories')
            ? DB::table('canteen_categories')->whereIn('canteen_id', $ids)->whereNull('deleted_at')
                ->selectRaw('canteen_id, count(*) c')->groupBy('canteen_id')->pluck('c', 'canteen_id')
            : collect();
        $prodCounts = \Illuminate\Support\Facades\Schema::hasTable('canteen_products')
            ? DB::table('canteen_products')->whereIn('canteen_id', $ids)->whereNull('deleted_at')
                ->selectRaw('canteen_id, count(*) c')->groupBy('canteen_id')->pluck('c', 'canteen_id')
            : collect();
        $canteens->each(function ($c) use ($catCounts, $prodCounts) {
            $c->categories_count = (int) ($catCounts[$c->id] ?? 0);
            $c->products_count = (int) ($prodCounts[$c->id] ?? 0);
        });

        return view('admin.canteens.index', compact('canteens', 'q'));
    }

    public function create(): View
    {
        return view('admin.canteens.create', [
            'canteen' => new Canteen(['is_active' => false]),
            'schools' => $this->schoolsForScope(),
            'grades' => $this->gradeList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCanteen($request);

        Canteen::create([
            'school_id' => $data['school_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'target_grades' => $data['target_grades'] ?? [],
            'is_active' => false, // always created inactive
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.canteens.index')
            ->with('status', __('canteen.flash.created'));
    }

    public function edit(int $id): View
    {
        $canteen = $this->findScoped($id);

        return view('admin.canteens.edit', [
            'canteen' => $canteen,
            'schools' => $this->schoolsForScope(),
            'grades' => $this->gradeList(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $canteen = $this->findScoped($id);
        $data = $this->validateCanteen($request);

        $canteen->update([
            'school_id' => $data['school_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'target_grades' => $data['target_grades'] ?? [],
        ]);

        return redirect()->route('admin.canteens.index')
            ->with('status', __('canteen.flash.updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->findScoped($id)->delete();

        return redirect()->route('admin.canteens.index')
            ->with('status', __('canteen.flash.deleted'));
    }

    public function managerForm(int $id): View
    {
        $canteen = $this->findScoped($id);

        return view('admin.canteens.manager', [
            'canteen' => $canteen,
            'admins' => $this->managerCandidates($canteen->school_id),
        ]);
    }

    public function assignManager(Request $request, int $id): RedirectResponse
    {
        $canteen = $this->findScoped($id);
        $data = $request->validate([
            'manager_id' => ['nullable', Rule::exists('users', 'id')],
        ]);

        $canteen->update(['manager_id' => $data['manager_id'] ?: null]);

        return redirect()->route('admin.canteens.index')
            ->with('status', __('canteen.flash.manager_assigned'));
    }

    public function activate(int $id): RedirectResponse
    {
        $canteen = $this->findScoped($id);
        $blockers = $canteen->activationBlockers();

        if (! empty($blockers)) {
            $reasons = implode('، ', array_map(fn ($k) => __($k), $blockers));

            return back()->with('error', __('canteen.flash.cannot_activate', ['reasons' => $reasons]));
        }

        $canteen->update(['is_active' => true]);

        return back()->with('status', __('canteen.flash.activated'));
    }

    public function deactivate(int $id): RedirectResponse
    {
        $this->findScoped($id)->update(['is_active' => false]);

        return back()->with('status', __('canteen.flash.deactivated'));
    }

    private function findScoped(int $id): Canteen
    {
        $schoolId = $this->activeSchoolId();

        return Canteen::query()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereKey($id)
            ->firstOrFail();
    }

    private function validateCanteen(Request $request): array
    {
        $schoolId = $this->activeSchoolId();

        return $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'school_id' => [
                'required',
                Rule::exists('schools', 'id')->where(fn ($q) => $schoolId ? $q->where('id', $schoolId) : $q),
            ],
            'target_grades' => ['nullable', 'array'],
            'target_grades.*' => ['integer', 'between:1,12'],
        ]);
    }
}
