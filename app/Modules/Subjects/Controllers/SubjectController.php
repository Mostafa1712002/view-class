<?php

namespace App\Modules\Subjects\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectLesson;
use App\Models\SubjectUnit;
use App\Modules\Subjects\Actions\ImportSubjectsFromExcelAction;
use App\Modules\Subjects\Repositories\Contracts\SubjectRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Mirror the same scope used by paginate(): when no school is resolved
        // (super-admin with no active school) count across all schools so the
        // KPI tiles stay consistent with the table below.
        $scoped = fn () => Subject::query()->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId));

        $stats = [
            'total' => $scoped()->count(),
            'active' => $scoped()->where('is_active', true)->count(),
            'core' => $scoped()->where('is_core', true)->count(),
            'templates' => $templatesCount,
        ];

        return view('admin.subjects.index', compact('subjects', 'templatesCount', 'stats'));
    }

    public function create(): View
    {
        $subject = new Subject;

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

    public function domains(int $id): View
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $domains = $subject->domains()->get();

        return view('admin.subjects.domains', compact('subject', 'domains'));
    }

    public function storeDomain(Request $request, int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $data['sort_order'] = (int) ($subject->domains()->max('sort_order') + 1);
        $subject->domains()->create($data);

        return redirect()
            ->route('admin.subjects.domains', $subject->id)
            ->with('success', __('domains.flash.added'));
    }

    public function updateDomain(Request $request, int $id, int $domainId): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $domain = $subject->domains()->whereKey($domainId)->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $domain->update($data);

        return redirect()
            ->route('admin.subjects.domains', $subject->id)
            ->with('success', __('domains.flash.updated'));
    }

    public function destroyDomain(int $id, int $domainId): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $subject = $this->subjects->findScoped($id, $schoolId);
        abort_if(! $subject, 404);

        $domain = $subject->domains()->whereKey($domainId)->firstOrFail();
        $domain->delete();

        return redirect()
            ->route('admin.subjects.domains', $subject->id)
            ->with('success', __('domains.flash.deleted'));
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

    /**
     * Stream the platform Excel template (one header row) the admin fills in
     * before bulk-importing subjects.
     */
    public function importTemplate(): StreamedResponse
    {
        $headers = [
            'الاسم بالعربي',
            'الاسم بالإنجليزي',
            'الاسم المختصر بالعربي',
            'الاسم المختصر بالإنجليزي',
            'لغة المادة',
            'الكود',
            'الشعبة',
            'الصف الدراسي',
            'الترتيب في الشهادة',
            'عدد الساعات',
            'عدد الحصص في الأسبوع',
            'القيمة المعتمدة',
        ];

        // A sample row to make the expected format obvious.
        $sample = ['الرياضيات', 'Mathematics', 'رياضيات', 'Math', 'عربي', 'MATH101', 'عام', '7', '1', '4', '5', '5'];

        $filename = 'subjects_import_template.csv';

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens the Arabic headers correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importStore(Request $request, ImportSubjectsFromExcelAction $action): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $schoolId = $this->activeSchoolId();

        try {
            $result = $action->execute($request->file('file'), $schoolId);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.subjects.index')
                ->with('error', $e->getMessage());
        }

        $message = __('sprint4.subjects.import.summary', [
            'created' => $result->created,
            'skipped' => $result->skipped,
            'failed' => $result->failed,
        ]);

        $redirect = redirect()->route('admin.subjects.index');

        if ($result->created > 0) {
            return $redirect->with('success', $message);
        }

        return $redirect->with('error', $message);
    }

    public function creditHours(Request $request): View
    {
        $schoolId = $this->activeSchoolId();

        // Build grade-level dropdown options. ViewClass uses level 1-12 stored as
        // JSON-stringified ints in subjects.grade_levels (e.g. ["1","2"]). Show the
        // human label that matches Saudi naming conventions.
        $gradeOptions = $this->gradeLevelOptions();

        $selectedLevel = (int) $request->query('grade_level', 0);
        $subjects = [];

        if ($selectedLevel > 0) {
            $subjects = $this->subjects->subjectsForGradeLevel($schoolId, $selectedLevel);
        }

        return view('admin.subjects.credit-hours', [
            'gradeOptions' => $gradeOptions,
            'selectedLevel' => $selectedLevel,
            'subjects' => $subjects,
        ]);
    }

    public function saveCreditHours(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        $hours = (array) $request->input('credit_hours', []);
        $active = (array) $request->input('credit_hours_active', []);
        $level = (int) $request->input('grade_level', 0);

        $count = $this->subjects->bulkSetCreditValues($schoolId, $hours, $active);

        $params = $level > 0 ? ['grade_level' => $level] : [];

        return redirect()
            ->route('admin.subjects.credit-hours', $params)
            ->with('success', __('sprint4.subjects.flash.credit_hours_saved', ['count' => $count]));
    }

    /**
     * Grade levels 1..12 with Saudi-style labels (الأول الابتدائي … الثالث الثانوي).
     *
     * @return array<int,string>
     */
    private function gradeLevelOptions(): array
    {
        $ordinals = [
            1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع',
            5 => 'الخامس', 6 => 'السادس',
        ];

        $out = [];
        // 1..6  Primary
        for ($g = 1; $g <= 6; $g++) {
            $out[$g] = $ordinals[$g].' الابتدائي';
        }
        // 7..9  Intermediate
        $intermediate = [7 => 'الأول', 8 => 'الثاني', 9 => 'الثالث'];
        foreach ($intermediate as $g => $ord) {
            $out[$g] = $ord.' المتوسط';
        }
        // 10..12 Secondary
        $secondary = [10 => 'الأول', 11 => 'الثاني', 12 => 'الثالث'];
        foreach ($secondary as $g => $ord) {
            $out[$g] = $ord.' الثانوي';
        }

        return $out;
    }

    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'short_name_ar' => ['nullable', 'string', 'max:120'],
            'short_name_en' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', 'string', 'in:ar,en'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'section' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:60'],
            'is_core' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'grade_levels' => ['nullable', 'array'],
            'grade_levels.*' => ['integer', 'min:1', 'max:12'],
            'credit_hours' => ['nullable', 'integer', 'min:0', 'max:50'],
            'total_hours' => ['nullable', 'integer', 'min:0', 'max:50'],
            'credit_value' => ['nullable', 'integer', 'min:0', 'max:50'],
            'certificate_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
