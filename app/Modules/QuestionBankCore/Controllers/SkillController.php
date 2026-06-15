<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\QuestionBankCore\Actions\Import\ParseSkillsExcel;
use App\Modules\QuestionBankCore\Models\SkillImportBatch;
use App\Modules\QuestionBankCore\Repositories\Contracts\SkillRepository;
use App\Modules\QuestionBankCore\Services\QbScopeService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * #248 — Skills (المهارات) admin CRUD + Excel import. School-scoped via
 * scopedSchoolId() (skills carry school_id; null scope = super-admin global).
 * Reads gated by canDo('skills.view'); writes by skills.create/edit/delete;
 * import by skills.import. Route group also applies role:super-admin,school-admin
 * so a non-privileged role gets a hard 403.
 */
class SkillController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private SkillRepository $skills,
        private QbScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canDo('skills.view'), 403);

        $schoolId = $this->scopedSchoolId();
        $filters = [
            'q'           => trim((string) $request->get('q', '')),
            'subject_id'  => $request->get('subject_id'),
            'semester_id' => $request->get('semester_id'),
            'skill_type'  => $request->get('skill_type'),
            'status'      => $request->get('status'),
        ];

        return view('admin.qb.taxonomy.skills.index', [
            'skills'    => $this->skills->paginateForAdmin($schoolId, $filters),
            'filters'   => $filters,
            'subjects'  => $this->scope->subjectsForSchool($schoolId),
            'semesters' => $schoolId ? $this->scope->semestersForSchool($schoolId) : collect(),
            'types'     => $this->typeLabels(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->canDo('skills.create'), 403);

        $schoolId = $this->scopedSchoolId();

        return view('admin.qb.taxonomy.skills.create', $this->formData($schoolId, new \App\Modules\QuestionBankCore\Models\Skill(['status' => 'active', 'skill_type' => 'normal'])));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('skills.create'), 403);

        $schoolId = $this->scopedSchoolId();
        $data = $this->validateSkill($request);
        $data['school_id'] = $schoolId; // scope-stamped (null only for super-admin)
        $data['created_by'] = auth()->id();

        $skill = $this->skills->create($data);
        ActivityLog::log('skills.create', "إضافة مهارة: {$skill->name} (#{$skill->id})", $skill);

        return redirect()->route('admin.qb.skills.index')->with('success', 'تمت إضافة المهارة.');
    }

    public function edit(int $skillId): View
    {
        abort_unless(auth()->user()->canDo('skills.edit'), 403);

        $skill = $this->loadScoped($skillId);

        return view('admin.qb.taxonomy.skills.edit', $this->formData($this->scopedSchoolId(), $skill));
    }

    public function update(Request $request, int $skillId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('skills.edit'), 403);

        $skill = $this->loadScoped($skillId);
        $data = $this->validateSkill($request);

        $this->skills->update($skill, $data);
        ActivityLog::log('skills.edit', "تعديل مهارة (#{$skill->id})", $skill);

        return redirect()->route('admin.qb.skills.index')->with('success', 'تم تحديث المهارة.');
    }

    public function destroy(int $skillId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('skills.delete'), 403);

        $skill = $this->loadScoped($skillId);
        $this->skills->delete($skill);
        ActivityLog::log('skills.delete', "حذف مهارة (#{$skill->id})", $skill);

        return redirect()->route('admin.qb.skills.index')->with('success', 'تم حذف المهارة.');
    }

    // ── Excel import ──────────────────────────────────────────────────────────

    public function importIndex(): View
    {
        abort_unless(auth()->user()->canDo('skills.import'), 403);

        $schoolId = $this->scopedSchoolId();
        $history = SkillImportBatch::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->latest('id')->limit(10)->get();

        return view('admin.qb.taxonomy.skills.import', compact('history'));
    }

    public function importTemplate(): BinaryFileResponse
    {
        abort_unless(auth()->user()->canDo('skills.import'), 403);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Skills');
        foreach (ParseSkillsExcel::COLUMNS as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.'1', $h);
        }
        $last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(ParseSkillsExcel::COLUMNS));
        $sheet->getStyle('A1:'.$last.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B8860B']],
        ]);
        $sample = ['قراءة الكلمات', 'اللغة العربية', 'الفصل الأول', '', '', '', '', '1', '', 'عادية', 'no', 'no', 'active'];
        foreach ($sample as $i => $v) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValueExplicit($col.'2', (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        for ($i = 1; $i <= count(ParseSkillsExcel::COLUMNS); $i++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'skills_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);

        return response()->download($tmp, 'skills_import_template.xlsx')->deleteFileAfterSend(true);
    }

    public function importPreview(Request $request, ParseSkillsExcel $parser): View|RedirectResponse
    {
        abort_unless(auth()->user()->canDo('skills.import'), 403);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']]);

        $schoolId = $this->scopedSchoolId();
        $file = $request->file('file');

        try {
            $rows = $parser->execute($file, $schoolId);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'تعذر قراءة الملف: '.$e->getMessage()]);
        }
        if ($rows === []) {
            return back()->withErrors(['file' => 'الملف فارغ أو لا يحتوي على بيانات.']);
        }

        $valid = collect($rows)->where('status', 'valid')->count();
        $batch = SkillImportBatch::create([
            'school_id'         => $schoolId,
            'original_filename' => $file->getClientOriginalName(),
            'total_rows'        => count($rows),
            'valid_rows'        => $valid,
            'invalid_rows'      => count($rows) - $valid,
            'status'            => 'previewed',
            'preview_data'      => $rows,
            'created_by'        => auth()->id(),
        ]);

        return view('admin.qb.taxonomy.skills.import_preview', compact('batch', 'rows'));
    }

    public function importConfirm(int $batchId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('skills.import'), 403);

        $schoolId = $this->scopedSchoolId();
        $batch = SkillImportBatch::query()
            ->whereKey($batchId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->first();
        abort_if(! $batch, 404);

        $rows = $batch->preview_data ?? [];
        if ($rows === []) {
            return redirect()->route('admin.qb.skills.import.index')->withErrors(['file' => 'انتهت صلاحية المعاينة، أعد رفع الملف.']);
        }

        $now = now();
        $insert = [];
        foreach ($rows as $row) {
            if (($row['status'] ?? null) !== 'valid' || empty($row['payload'])) {
                continue;
            }
            $insert[] = array_merge($row['payload'], ['created_at' => $now, 'updated_at' => $now]);
        }
        $this->skills->insertMany($insert);

        $batch->update(['status' => 'imported', 'imported_rows' => count($insert)]);
        ActivityLog::log('skills.import', "استيراد مهارات من Excel (مستورد: ".count($insert).")", $batch);

        return redirect()->route('admin.qb.skills.index')
            ->with('success', 'تم استيراد '.count($insert).' مهارة بنجاح.');
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function loadScoped(int $skillId): \App\Modules\QuestionBankCore\Models\Skill
    {
        $skill = $this->skills->find($skillId);
        abort_if(! $skill, 404);
        // Scope guard: a non-super-admin may only touch their own school's skills
        // (global school_id=null skills are super-admin only).
        $schoolId = $this->scopedSchoolId();
        if ($schoolId !== null) {
            abort_unless((int) $skill->school_id === (int) $schoolId, 403);
        }

        return $skill;
    }

    private function validateSkill(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'subject_id'  => ['nullable', 'integer', 'exists:subjects,id'],
            'semester_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'week_id'     => ['nullable', 'integer', 'exists:study_weeks,id'],
            'skill_type'  => ['required', 'in:normal,ability,tahsili,verbal,quantitative'],
            'is_tahsili'  => ['nullable', 'boolean'],
            'is_ability'  => ['nullable', 'boolean'],
            'status'      => ['required', 'in:active,inactive'],
        ]);
    }

    private function formData(?int $schoolId, \App\Modules\QuestionBankCore\Models\Skill $skill): array
    {
        return [
            'skill'     => $skill,
            'subjects'  => $this->scope->subjectsForSchool($schoolId),
            'semesters' => $schoolId ? $this->scope->semestersForSchool($schoolId) : collect(),
            'types'     => $this->typeLabels(),
        ];
    }

    private function typeLabels(): array
    {
        return [
            'normal'       => 'عادية',
            'ability'      => 'قدرات',
            'tahsili'      => 'تحصيلي',
            'verbal'       => 'لفظي',
            'quantitative' => 'كمي',
        ];
    }
}
