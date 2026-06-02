<?php

namespace App\Modules\Libraries\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Libraries\Repositories\Contracts\LibraryItemRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicLibraryController extends Controller
{
    use HasSchoolScope;

    public function __construct(private LibraryItemRepository $items) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $filters = $request->only(['title', 'content_type', 'subject_id', 'teacher_id', 'tag', 'sort']);
        $items = $this->items->paginatePublic($schoolId, $filters);

        $subjects = Subject::query()
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id');
                if ($schoolId) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $teachers = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name']);

        $types = LibraryItem::TYPES;

        return view('admin.libraries.public.index', compact('items', 'filters', 'subjects', 'teachers', 'types'));
    }

    public function create(): View
    {
        $item = new LibraryItem;

        return view('admin.libraries.public.create', $this->formData($item));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateItem($request);
        $data['is_public'] = true;
        $data['library_id'] = null;
        $data['school_id'] = $this->activeSchoolId();
        $data['created_by'] = auth()->id();
        $data = $this->handleUploads($request, $data);

        $this->items->create($data);

        return redirect()->route('admin.libraries.public.index')
            ->with('success', __('libraries.flash.item_created'));
    }

    public function edit(int $id): View
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        return view('admin.libraries.public.edit', $this->formData($item));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        $data = $this->validateItem($request);
        $data = $this->handleUploads($request, $data, $item);

        $this->items->update($item, $data);

        return redirect()->route('admin.libraries.public.index')
            ->with('success', __('libraries.flash.item_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = $this->items->findScoped($id, $this->activeSchoolId());
        abort_if(! $item, 404);

        $this->items->delete($item);

        return redirect()->route('admin.libraries.public.index')
            ->with('success', __('libraries.flash.item_deleted'));
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content_type' => ['required', 'in:'.implode(',', LibraryItem::TYPES)],
            'external_url' => ['nullable', 'url', 'max:1024'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'file' => ['nullable', 'file', 'max:51200'], // 50MB
            'thumbnail' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    private function handleUploads(Request $request, array $data, ?LibraryItem $existing = null): array
    {
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('libraries/items', 'public');
            $data['file_path'] = $path;
            if ($existing && $existing->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }
        }
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('libraries/thumbs', 'public');
            $data['thumbnail_path'] = $path;
            if ($existing && $existing->thumbnail_path) {
                Storage::disk('public')->delete($existing->thumbnail_path);
            }
        }
        unset($data['file'], $data['thumbnail']);

        return $data;
    }

    private function formData(LibraryItem $item): array
    {
        $schoolId = $this->activeSchoolId();

        $subjects = Subject::query()
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id');
                if ($schoolId) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $teachers = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        $types = LibraryItem::TYPES;

        return compact('item', 'subjects', 'teachers', 'types');
    }
}
