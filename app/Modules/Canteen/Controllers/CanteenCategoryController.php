<?php

namespace App\Modules\Canteen\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\CanteenCategory;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CanteenCategoryController extends Controller
{
    use HasSchoolScope;

    private function findCanteen(int $canteenId): Canteen
    {
        $schoolId = $this->activeSchoolId();

        return Canteen::query()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereKey($canteenId)
            ->firstOrFail();
    }

    public function index(int $canteenId): View
    {
        $canteen = $this->findCanteen($canteenId);
        $categories = $canteen->categories()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.canteens.categories.index', compact('canteen', 'categories'));
    }

    public function store(Request $request, int $canteenId): RedirectResponse
    {
        $canteen = $this->findCanteen($canteenId);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $canteen->categories()->create([
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('status', __('canteen.categories.flash.created'));
    }

    public function update(Request $request, int $canteenId, int $id): RedirectResponse
    {
        $canteen = $this->findCanteen($canteenId);
        $category = $canteen->categories()->whereKey($id)->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('status', __('canteen.categories.flash.updated'));
    }

    public function toggle(int $canteenId, int $id): RedirectResponse
    {
        $canteen = $this->findCanteen($canteenId);
        $category = $canteen->categories()->whereKey($id)->firstOrFail();
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('status', __('canteen.categories.flash.updated'));
    }

    public function destroy(int $canteenId, int $id): RedirectResponse
    {
        $canteen = $this->findCanteen($canteenId);
        $category = $canteen->categories()->whereKey($id)->firstOrFail();

        // A category with products can't be deleted (no product without a category).
        if ($category->products()->exists()) {
            return back()->with('error', __('canteen.categories.flash.has_products'));
        }

        $category->delete();

        return back()->with('status', __('canteen.categories.flash.deleted'));
    }
}
