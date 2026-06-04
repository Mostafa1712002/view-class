<?php

namespace App\Modules\Canteen\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use App\Models\CanteenProduct;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CanteenProductController extends Controller
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

    private function categories(Canteen $canteen)
    {
        return $canteen->categories()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
    }

    public function index(int $canteenId): View
    {
        $canteen = $this->findCanteen($canteenId);
        $products = $canteen->products()->with('category')->orderBy('sort_order')->orderByDesc('id')->get();

        return view('admin.canteens.products.index', compact('canteen', 'products'));
    }

    public function create(int $canteenId): View
    {
        $canteen = $this->findCanteen($canteenId);

        return view('admin.canteens.products.create', [
            'canteen' => $canteen,
            'product' => new CanteenProduct(['is_active' => true, 'price' => 0]),
            'categories' => $this->categories($canteen),
        ]);
    }

    public function store(Request $request, int $canteenId): RedirectResponse
    {
        $canteen = $this->findCanteen($canteenId);
        $data = $this->validateProduct($request, $canteen);

        $product = new CanteenProduct([
            'canteen_id' => $canteen->id,
            'canteen_category_id' => $data['canteen_category_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'calories' => $data['calories'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
        if ($request->hasFile('image')) {
            $product->image_path = $request->file('image')->store('canteen/products', 'public');
        }
        $product->save();

        return redirect()->route('admin.canteens.products.index', $canteen->id)
            ->with('status', __('canteen.products.flash.created'));
    }

    public function edit(int $canteenId, int $id): View
    {
        $canteen = $this->findCanteen($canteenId);
        $product = $canteen->products()->whereKey($id)->firstOrFail();

        return view('admin.canteens.products.edit', [
            'canteen' => $canteen,
            'product' => $product,
            'categories' => $this->categories($canteen),
        ]);
    }

    public function update(Request $request, int $canteenId, int $id): RedirectResponse
    {
        $canteen = $this->findCanteen($canteenId);
        $product = $canteen->products()->whereKey($id)->firstOrFail();
        $data = $this->validateProduct($request, $canteen);

        $product->fill([
            'canteen_category_id' => $data['canteen_category_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'calories' => $data['calories'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->image_path = $request->file('image')->store('canteen/products', 'public');
        }
        $product->save();

        return redirect()->route('admin.canteens.products.index', $canteen->id)
            ->with('status', __('canteen.products.flash.updated'));
    }

    public function toggle(int $canteenId, int $id): RedirectResponse
    {
        $canteen = $this->findCanteen($canteenId);
        $product = $canteen->products()->whereKey($id)->firstOrFail();
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', __('canteen.products.flash.updated'));
    }

    public function destroy(int $canteenId, int $id): RedirectResponse
    {
        $canteen = $this->findCanteen($canteenId);
        $product = $canteen->products()->whereKey($id)->firstOrFail();
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()->route('admin.canteens.products.index', $canteen->id)
            ->with('status', __('canteen.products.flash.deleted'));
    }

    private function validateProduct(Request $request, Canteen $canteen): array
    {
        return $request->validate([
            // Category must belong to THIS canteen (no product without a valid category).
            'canteen_category_id' => [
                'required',
                Rule::exists('canteen_categories', 'id')->where(fn ($q) => $q->where('canteen_id', $canteen->id)->whereNull('deleted_at')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'calories' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
    }
}
