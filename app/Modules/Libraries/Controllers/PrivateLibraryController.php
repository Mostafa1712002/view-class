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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $currentAudiences = $library->exists
            ? LibraryAudience::where('library_id', $library->id)->get()->groupBy('audience_type')
            : collect();

        // Students/teachers are loaded dynamically per selected class (card #119), so we only
        // need to pre-render the ones already attached to this library (edit screen).
        $selectedStudentIds = collect($currentAudiences['user'] ?? [])->pluck('audience_id')->all();
        $selectedTeacherIds = collect($currentAudiences['teacher'] ?? [])->pluck('audience_id')->all();

        $selectedStudents = empty($selectedStudentIds) ? collect()
            : User::query()->whereIn('id', $selectedStudentIds)->orderBy('name')->get(['id', 'name']);
        $selectedTeachers = empty($selectedTeacherIds) ? collect()
            : User::query()->whereIn('id', $selectedTeacherIds)->orderBy('name')->get(['id', 'name']);

        return compact('library', 'classes', 'currentAudiences', 'selectedStudents', 'selectedTeachers');
    }

    /**
     * AJAX endpoint (card #119): return the students and teachers linked to the
     * selected class(es) so the private-library audience selects cascade from the
     * chosen class instead of listing every user in the school.
     */
    public function classMembers(Request $request): JsonResponse
    {
        $classIds = collect((array) $request->input('class_ids', []))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->values();

        if ($classIds->isEmpty()) {
            return response()->json(['students' => [], 'teachers' => []]);
        }

        $schoolId = $this->activeSchoolId();

        // Keep only classes the current scope is allowed to see.
        $scopedClassIds = ClassRoom::query()
            ->whereIn('id', $classIds)
            ->when($schoolId, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
            ->pluck('id');

        if ($scopedClassIds->isEmpty()) {
            return response()->json(['students' => [], 'teachers' => []]);
        }

        $students = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->whereIn('id', function ($q) use ($scopedClassIds) {
                $q->select('student_id')->from('class_student')->whereIn('class_id', $scopedClassIds);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        // Teachers linked to a class = its lead teacher + teachers of its periods +
        // teachers in its schedule periods (including substitutes).
        $teacherIds = ClassRoom::query()->whereIn('id', $scopedClassIds)->pluck('lead_teacher_id');
        $teacherIds = $teacherIds
            ->merge(DB::table('class_periods')->whereIn('class_id', $scopedClassIds)->pluck('teacher_id'))
            ->merge(DB::table('class_periods')->whereIn('class_id', $scopedClassIds)->pluck('substitute_teacher_id'));

        $scheduleIds = DB::table('schedules')->whereIn('class_id', $scopedClassIds)->pluck('id');
        if ($scheduleIds->isNotEmpty()) {
            $teacherIds = $teacherIds
                ->merge(DB::table('schedule_periods')->whereIn('schedule_id', $scheduleIds)->pluck('teacher_id'))
                ->merge(DB::table('schedule_periods')->whereIn('schedule_id', $scheduleIds)->pluck('substitute_teacher_id'));
        }

        $teacherIds = $teacherIds->filter()->map(fn ($v) => (int) $v)->unique()->values();

        $teachers = $teacherIds->isEmpty() ? collect()
            : User::query()
                ->whereIn('id', $teacherIds)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))
                ->orderBy('name')
                ->get(['id', 'name']);

        return response()->json([
            'students' => $students->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'teachers' => $teachers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
        ]);
    }
}
