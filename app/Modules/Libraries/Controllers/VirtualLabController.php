<?php

namespace App\Modules\Libraries\Controllers;

use App\Http\Controllers\Controller;
use App\Models\VirtualLab;
use App\Models\VirtualLabCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VirtualLabController extends Controller
{
    public function index(Request $request): View
    {
        $categories = VirtualLabCategory::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $activeCategory = null;
        if ($request->filled('category')) {
            $activeCategory = VirtualLabCategory::query()->where('slug', $request->get('category'))->first();
        }

        $labs = VirtualLab::query()
            ->where('is_active', true)
            ->when($activeCategory, function ($q) use ($activeCategory) {
                $childIds = $activeCategory->children()->pluck('id')->push($activeCategory->id);
                $q->whereIn('category_id', $childIds);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        return view('admin.libraries.labs.index', compact('categories', 'activeCategory', 'labs'));
    }

    /**
     * Student-facing virtual labs (card #173).
     *
     * Labs are platform-wide (no school_id / subject_id columns exist on
     * virtual_labs), so every active lab is treated as public to students.
     * Category sidebar + experiment cards reuse the same lab grid as admin.
     */
    public function studentIndex(Request $request): View
    {
        $categories = VirtualLabCategory::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $activeCategory = null;
        if ($request->filled('category')) {
            $activeCategory = VirtualLabCategory::query()->where('slug', $request->get('category'))->first();
        }

        $labs = VirtualLab::query()
            ->where('is_active', true)
            ->when($activeCategory, function ($q) use ($activeCategory) {
                $childIds = $activeCategory->children()->pluck('id')->push($activeCategory->id);
                $q->whereIn('category_id', $childIds);
            })
            ->with('category')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        return view('student.labs.index', compact('categories', 'activeCategory', 'labs'));
    }

    public function manage(): View
    {
        $labs = VirtualLab::query()
            ->with('category')
            ->orderByDesc('id')
            ->paginate(20);
        return view('admin.libraries.labs.manage', compact('labs'));
    }

    public function create(): View
    {
        $lab = new VirtualLab(['is_active' => true]);
        $categories = VirtualLabCategory::orderBy('name_ar')->get();
        return view('admin.libraries.labs.create', compact('lab', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateLab($request);
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = $request->file('thumbnail')->store('libraries/labs', 'public');
        }
        unset($data['thumbnail']);
        VirtualLab::create($data);
        return redirect()->route('admin.libraries.labs.manage')
            ->with('success', __('libraries.flash.lab_created'));
    }

    public function edit(int $id): View
    {
        $lab = VirtualLab::query()->whereKey($id)->firstOrFail();
        $categories = VirtualLabCategory::orderBy('name_ar')->get();
        return view('admin.libraries.labs.edit', compact('lab', 'categories'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $lab = VirtualLab::query()->whereKey($id)->firstOrFail();
        $data = $this->validateLab($request);
        if ($request->hasFile('thumbnail')) {
            if ($lab->thumbnail_path) {
                Storage::disk('public')->delete($lab->thumbnail_path);
            }
            $data['thumbnail_path'] = $request->file('thumbnail')->store('libraries/labs', 'public');
        }
        unset($data['thumbnail']);
        $lab->update($data);
        return redirect()->route('admin.libraries.labs.manage')
            ->with('success', __('libraries.flash.lab_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $lab = VirtualLab::query()->whereKey($id)->firstOrFail();
        if ($lab->thumbnail_path) {
            Storage::disk('public')->delete($lab->thumbnail_path);
        }
        $lab->delete();
        return redirect()->route('admin.libraries.labs.manage')
            ->with('success', __('libraries.flash.lab_deleted'));
    }

    public function show(int $id): View
    {
        $lab = VirtualLab::query()->where('is_active', true)->whereKey($id)->firstOrFail();
        return view('admin.libraries.labs.show', compact('lab'));
    }

    private function validateLab(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:virtual_lab_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'external_url' => ['nullable', 'url', 'max:1024'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'thumbnail' => ['nullable', 'image', 'max:5120'],
        ]);
    }
}
