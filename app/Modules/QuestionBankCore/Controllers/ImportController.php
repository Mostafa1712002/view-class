<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportError;
use App\Modules\QuestionBankCore\Actions\Import\GenerateImportTemplate;
use App\Modules\QuestionBankCore\Actions\Import\ImportFromPreview;
use App\Modules\QuestionBankCore\Actions\Import\ParseQuestionsExcel;
use App\Modules\QuestionBankCore\Repositories\Contracts\QuestionRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * #254 — Excel import for the QB rebuild (admin/qb). Mirrors the manual-create
 * gating: canDo('question_banks.import') on every action + scopedSchoolId()
 * (fail-closed). Routes to the existing CreateQuestion action via ImportFromPreview
 * so imports write bank_questions + question_answers + answer_data identically.
 */
class ImportController extends Controller
{
    use HasSchoolScope;

    public function __construct(private QuestionRepository $questions) {}

    /** Upload screen: bank picker + template/upload buttons + recent batches. */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canDo('question_banks.import'), 403);

        $schoolId = $this->scopedSchoolId();
        $banks = $this->questions->banksForScope($schoolId);

        $history = QuestionImportBatch::query()
            ->where('import_type', 'questions')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.qb.import.index', compact('banks', 'history'));
    }

    /** Stream the xlsx template (scope-validated bank). */
    public function template(Request $request, GenerateImportTemplate $generator): BinaryFileResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.import'), 403);

        $bank = $this->resolveBank((int) $request->get('bank_id'));
        $path = $generator->execute($bank);

        return response()->download($path, 'qb_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Step 1 — parse + validate, persist preview, render the preview table. */
    public function preview(Request $request, ParseQuestionsExcel $parser): View|RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.import'), 403);

        $request->validate([
            'question_bank_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'status_choice' => ['nullable', 'in:draft,pending_review,approved'],
            // "update" existing is deferred — UI offers skip|new only.
            'duplicate_policy' => ['nullable', 'in:skip,new'],
        ]);

        $schoolId = $this->scopedSchoolId();
        $bank = $this->resolveBank((int) $request->input('question_bank_id'));

        $file = $request->file('file');
        $stored = $file->store('qb-imports/'.date('Y/m'), 'local');

        $batch = QuestionImportBatch::create([
            'question_bank_id' => $bank->id,
            'import_type' => 'questions',
            'school_id' => $bank->school_id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $stored,
            'status' => 'previewed',
            'created_by' => auth()->id(),
            'imported_by' => auth()->id(),
            'settings' => [
                'status_choice' => $request->input('status_choice', 'draft'),
                'duplicate_policy' => $request->input('duplicate_policy', 'skip'),
            ],
        ]);

        try {
            $rows = $parser->execute($file, $bank, $schoolId);
        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed']);

            return back()->withErrors(['file' => 'تعذر قراءة الملف: '.$e->getMessage()])->withInput();
        }

        if (empty($rows)) {
            $batch->update(['status' => 'failed']);

            return back()->withErrors(['file' => 'الملف فارغ أو لا يحتوي على صفوف بيانات.'])->withInput();
        }

        $counts = $this->countStatuses($rows);
        $batch->update([
            'total_rows' => count($rows),
            'valid_rows' => $counts['valid'],
            'invalid_rows' => $counts['invalid'] + $counts['duplicate'],
            'preview_data' => $rows,
        ]);

        return view('admin.qb.import.preview', compact('bank', 'batch', 'rows', 'counts'));
    }

    /** Step 2 — import the valid rows. */
    public function confirm(Request $request, int $batchId, ImportFromPreview $importer): View|RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.import'), 403);

        $batch = $this->findOwnedBatch($batchId);
        abort_if(! $batch, 404);

        $rows = $batch->preview_data ?? [];
        if (empty($rows)) {
            return redirect()->route('admin.qb.import.index')
                ->withErrors(['file' => 'انتهت صلاحية المعاينة، يرجى رفع الملف من جديد.']);
        }

        $bank = $this->resolveBank((int) $batch->question_bank_id);
        $settings = $batch->settings ?? [];

        $result = $importer->execute(
            $bank,
            $batch,
            $rows,
            $settings['status_choice'] ?? 'draft',
            $settings['duplicate_policy'] ?? 'skip',
            auth()->id(),
        );

        ActivityLog::log(
            'question_banks.import',
            "استيراد أسئلة من Excel إلى بنك {$bank->name_ar} (مستورد: {$result->imported}، فاشل: {$result->failed})",
            $batch
        );

        return view('admin.qb.import.result', compact('bank', 'batch', 'result'));
    }

    /** Download the per-row error report (UTF-8 BOM CSV). */
    public function errorReport(int $batchId): StreamedResponse|RedirectResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.import'), 403);

        $batch = $this->findOwnedBatch($batchId);
        abort_if(! $batch, 404);

        $errors = QuestionImportError::where('import_batch_id', $batch->id)
            ->orderBy('row_number')
            ->get();

        $filename = 'qb-import-errors-'.$batch->id.'.csv';

        return response()->streamDownload(function () use ($errors) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $safe = fn ($v) => $this->csvSafe($v);
            fputcsv($out, array_map($safe, ['رقم الصف', 'كود السؤال', 'نوع الخطأ', 'رسالة الخطأ', 'البيانات']));
            foreach ($errors as $err) {
                fputcsv($out, array_map($safe, [
                    $err->row_number,
                    $err->question_code,
                    $err->error_type,
                    $err->error_message ?: implode(' | ', $err->errors ?? []),
                    json_encode($err->raw ?? [], JSON_UNESCAPED_UNICODE),
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function resolveBank(int $bankId): \App\Models\QuestionBank
    {
        $schoolId = $this->scopedSchoolId();
        $bank = $this->questions->findBankScoped($bankId, $schoolId);
        abort_if(! $bank, 404, 'بنك الأسئلة غير موجود أو خارج نطاقك.');

        return $bank;
    }

    private function findOwnedBatch(int $batchId): ?QuestionImportBatch
    {
        $schoolId = $this->scopedSchoolId();

        return QuestionImportBatch::query()
            ->whereKey($batchId)
            ->where('import_type', 'questions')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->first();
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     * @return array{valid:int,invalid:int,duplicate:int}
     */
    private function countStatuses(array $rows): array
    {
        $c = ['valid' => 0, 'invalid' => 0, 'duplicate' => 0];
        foreach ($rows as $r) {
            $s = $r['status'] ?? 'valid';
            if (isset($c[$s])) {
                $c[$s]++;
            }
        }

        return $c;
    }

    private function csvSafe($value): string
    {
        $s = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $s) ? "'".$s : $s;
    }
}
