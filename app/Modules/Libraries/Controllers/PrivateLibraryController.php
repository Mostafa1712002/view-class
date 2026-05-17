<?php

namespace App\Modules\Libraries\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Library;
use App\Models\LibraryAudience;
use App\Models\LibraryItem;
use App\Models\User;
use App\Modules\Libraries\Repositories\Contracts\LibraryItemRepository;
use App\Modules\Libraries\Repositories\Contracts\LibraryRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PrivateLibraryController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private LibraryRepository $libraries,
        private LibraryItemRepository $items,
    ) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $libraries = $this->libraries->paginatePrivate($schoolId, $request->get('q'));
        return view('admin.libraries.private.index', compact('libraries'));
    }

    public function create(): View
    {
        $library = new Library(['type' => 'private', 'is_active' => true]);
        return view('admin.libraries.private.create', $this->formData($library));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateLibrary($request);
        $audiences = $this->parseAudiences($request);
        $data['type'] = 'private';
        $data['school_id'] = $this->activeSchoolId();
        $data['created_by'] = auth()->id();

        $library = $this->libraries->create($data);
        $this->libraries->syncAudiences($library, $audiences);

        return redirect()->route('admin.libraries.private.edit', $library->id)
            ->with('success', __('libraries.flash.library_created'));
    }

    public function edit(int $id): View
    {
        $library = $this->libraries->findScoped($id, $this->activeSchoolId());
        abort_if(! $library, 404);
        return view('admin.libraries.private.edit', $this->formData($library));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $library = $this->libraries->findScoped($id, $this->activeSchoolId());
        abort_if(! $library, 404);

        $data = $this->validateLibrary($request);
        $audiences = $this->parseAudiences($request);

        $this->libraries->update($library, $data);
        $this->libraries->syncAudiences($library, $audiences);

        return redirect()->route('admin.libraries.private.edit', $library->id)
            ->with('success', __('libraries.flash.library_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $library = $this->libraries->findScoped($id, $this->activeSchoolId());
        abort_if(! $library, 404);
        $this->libraries->delete($library);

        return redirect()->route('admin.libraries.private.index')
            ->with('success', __('libraries.flash.library_deleted'));
    }

    public function items(Request $request, int $id): View
    {
        $library = $this->libraries->findScoped($id, $this->activeSchoolId());
        abort_if(! $library, 404);
        $items = $this->items->paginateForLibrary($library->id, $this->activeSchoolId(), $request->get('q'));
        $types = LibraryItem::TYPES;
        return view('admin.libraries.private.items', compact('library', 'items', 'types'));
    }

    public function storeItem(Request $request, int $id): RedirectResponse
    {
        $library = $this->libraries->findScoped($id, $this->activeSchoolId());
        abort_if(! $library, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content_type' => ['required', 'in:' . implode(',', LibraryItem::TYPES)],
            'external_url' => ['nullable', 'url', 'max:1024'],
            'file' => ['nullable', 'file', 'max:51200'],
        ]);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('libraries/items', 'public');
        }
        unset($data['file']);

        $data['library_id'] = $library->id;
        $data['school_id'] = $library->school_id;
        $data['is_public'] = false;
        $data['created_by'] = auth()->id();

        $this->items->create($data);

        return redirect()->route('admin.libraries.private.items', $library->id)
            ->with('success', __('libraries.flash.item_created'));
    }

    public function destroyItem(int $id, int $itemId): RedirectResponse
    {
        $library = $this->libraries->findScoped($id, $this->activeSchoolId());
        abort_if(! $library, 404);
        $item = LibraryItem::query()->where('library_id', $library->id)->whereKey($itemId)->first();
        abort_if(! $item, 404);
        if ($item->file_path) {
            Storage::disk('public')->delete($item->file_path);
        }
        $item->delete();
        return back()->with('success', __('libraries.flash.item_deleted'));
    }

    private function validateLibrary(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function parseAudiences(Request $request): array
    {
        $raw = $request->input('audiences', []);
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $type = $entry['type'] ?? null;
            $ids = $entry['ids'] ?? [];
            if (! is_array($ids)) {
                $ids = [$ids];
            }
            foreach ($ids as $id) {
                if ($id === '' || $id === null) {
                    continue;
                }
                $out[] = ['type' => $type, 'id' => $id];
            }
        }
        return $out;
    }

    private function formData(Library $library): array
    {
        $schoolId = $this->activeSchoolId();

        $classes = ClassRoom::query()
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId));
            })
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        $students = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        $teachers = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        $currentAudiences = $library->exists
            ? LibraryAudience::where('library_id', $library->id)->get()->groupBy('audience_type')
            : collect();

        return compact('library', 'classes', 'students', 'teachers', 'currentAudiences');
    }
}
