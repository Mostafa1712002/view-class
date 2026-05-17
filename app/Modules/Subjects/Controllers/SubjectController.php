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

        $stats = [
            'total' => Subject::query()->where('school_id', $schoolId)->count(),
            'active' => Subject::query()->where('school_id', $schoolId)->where('is_active', true)->count(),
            'core' => Subject::query()->where('school_id', $schoolId)->where('is_core', true)->count(),
            'templates' => $templatesCount,
        ];

        return view('admin.subjects.index', compact('subjects', 'templatesCount', 'stats'));
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

    public function templatesIndex(): View
    {
        $schoolId = $this->activeSchoolId();
        $templates = collect($this->subjects->platformTemplates());

        // Group templates by the first grade level they target so the UI can show one section per grade.
        $byGrade = [];
        foreach ($templates as $template) {
            $levels = is_array($template->grade_levels) && count($template->grade_levels) > 0
                ? $template->grade_levels
                : [0]; // 0 = ungraded bucket
            foreach ($levels as $level) {
                $byGrade[(int) $level][] = $template;
            }
        }
        ksort($byGrade);

        // Which template IDs are already added to this school?
        $alreadyAdded = Subject::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('template_subject_id')
            ->pluck('template_subject_id')
            ->all();

        return view('admin.subjects.templates', [
            'byGrade' => $byGrade,
            'alreadyAdded' => $alreadyAdded,
            'total' => $templates->count(),
        ]);
    }

    public function templatesAttach(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $ids = array_map('intval', (array) $request->input('template_ids', []));
        $ids = array_filter($ids);

        if (empty($ids)) {
            return redirect()
                ->route('admin.subjects.templates.index')
                ->with('error', __('sprint4.subjects.add_template_no_selection'));
        }

        // Load only true platform templates (school_id NULL).
        $templates = Subject::query()->whereNull('school_id')->whereIn('id', $ids)->get();

        // Skip templates already added to this school to keep the operation idempotent.
        $existing = Subject::query()
            ->where('school_id', $schoolId)
            ->whereIn('template_subject_id', $templates->pluck('id'))
            ->pluck('template_subject_id')
            ->all();

        $count = 0;
        foreach ($templates as $template) {
            if (in_array($template->id, $existing, true)) {
                continue;
            }
            $this->subjects->create([
                'school_id' => $schoolId,
                'name' => $template->name,
                'name_en' => $template->name_en,
                'code' => $template->code,
                'description' => $template->description,
                'is_core' => (bool) $template->is_core,
                'is_active' => true,
                'grade_levels' => $template->grade_levels,
                'section' => $template->section,
                'credit_hours' => $template->credit_hours,
                'certificate_order' => $template->certificate_order,
                'source' => 'viewclass',
                'template_subject_id' => $template->id,
            ]);
            $count++;
        }

        return redirect()
            ->route('admin.subjects.index')
            ->with('success', __('sprint4.subjects.add_template_success', ['count' => $count]));
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
