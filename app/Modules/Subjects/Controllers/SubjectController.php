<?php

namespace App\Modules\Subjects\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectLesson;
use App\Models\SubjectUnit;
use App\Modules\Subjects\Repositories\Contracts\SubjectRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SubjectRepository $subjects) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $subjects = $this->subjects->paginate($schoolId, $request->get('q'));
        $templatesCount = $this->subjects->platformTemplates() instanceof \Illuminate\Support\Collection
            ? $this->subjects->platformTemplates()->count()
            : count((array) $this->subjects->platformTemplates());

        return view('admin.subjects.index', compact('subjects', 'templatesCount'));
    }

    public function create(): View
    {
        $subject = new Subject();
        return view('admin.subjects.create', compact('subject'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSubject($request);
        $data['school_id'] = $this->activeSchoolId();
        $data['source'] = 'manual';

        $this->subjects->create($data);

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', __('sprint4.subjects.flash.created'));
    }

    public function edit(int $id): View
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $data = $this->validateSubject($request, $subject);
        $this->subjects->update($subject, $data);

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', __('sprint4.subjects.flash.updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $this->subjects->delete($subject);

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', __('sprint4.subjects.flash.deleted'));
    }

    public function lessonTree(int $id): View
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        return view('admin.subjects.lesson-tree', compact('subject'));
    }

    public function storeUnit(Request $request, int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);
        $data['subject_id'] = $subject->id;
        $data['sort_order'] = (int) ($subject->units()->max('sort_order') + 1);
        SubjectUnit::create($data);

        return redirect()
            ->route('admin.subjects.lesson-tree', $subject->id)
            ->with('success', __('sprint4.subjects.flash.unit_added'));
    }

    public function destroyUnit(int $id, int $unitId): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $unit = $subject->units()->whereKey($unitId)->firstOrFail();
        $unit->delete();

        return redirect()
            ->route('admin.subjects.lesson-tree', $subject->id)
            ->with('success', __('sprint4.subjects.flash.unit_deleted'));
    }

    public function storeLesson(Request $request, int $id, int $unitId): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $unit = $subject->units()->whereKey($unitId)->firstOrFail();

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);
        $data['unit_id'] = $unit->id;
        $data['sort_order'] = (int) ($unit->lessons()->max('sort_order') + 1);
        SubjectLesson::create($data);

        return redirect()
            ->route('admin.subjects.lesson-tree', $subject->id)
            ->with('success', __('sprint4.subjects.flash.lesson_added'));
    }

    public function destroyLesson(int $id, int $unitId, int $lessonId): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $unit = $subject->units()->whereKey($unitId)->firstOrFail();
        $lesson = $unit->lessons()->whereKey($lessonId)->firstOrFail();
        $lesson->delete();

        return redirect()
            ->route('admin.subjects.lesson-tree', $subject->id)
            ->with('success', __('sprint4.subjects.flash.lesson_deleted'));
    }

    public function creditHours(): View
    {
        $schoolId = $this->activeSchoolId();
        $subjects = $this->subjects->paginate($schoolId, null, 100);
        return view('admin.subjects.credit-hours', compact('subjects'));
    }

    public function saveCreditHours(Request $request): RedirectResponse
    {
        $hours = $request->input('credit_hours', []);
        $schoolId = $this->activeSchoolId();
        $count = $this->subjects->bulkSetCreditHours($schoolId, is_array($hours) ? $hours : []);

        return redirect()
            ->route('admin.subjects.credit-hours')
            ->with('success', __('sprint4.subjects.flash.credit_hours_saved', ['count' => $count]));
    }

    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'section' => ['nullable', 'string', 'max:120'],
            'is_core' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'grade_levels' => ['nullable', 'array'],
            'grade_levels.*' => ['integer', 'min:1', 'max:12'],
            'credit_hours' => ['nullable', 'integer', 'min:0', 'max:20'],
            'certificate_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
