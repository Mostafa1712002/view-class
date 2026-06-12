<?php

namespace App\Modules\QuestionBanks\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportError;
use App\Modules\QuestionBanks\Actions\GenerateQuestionImportTemplate;
use App\Modules\QuestionBanks\Actions\ImportQuestionsAction;
use App\Modules\QuestionBanks\Actions\ParseQuestionsExcel;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles the Excel import flow for questions within a specific bank.
 *
 * Routes:
 *   GET  .../questions/import              → form()
 *   GET  .../questions/import/template     → template()
 *   POST .../questions/import/preview      → preview()
 *   POST .../questions/import/{batch}/execute → execute()
 *   GET  .../questions/import/{batch}/errors.csv → errorsReport()
 *
 * Gating: uses the same resolveBank() mechanism as BankQuestionController
 * (multi-tenant school scope via HasSchoolScope::activeSchoolId()).
 *
 * TODO #217: replace with granular QB import permission once the
 * permission catalog is introduced.
 */
class QuestionImportController extends Controller
{
    use HasSchoolScope;

    public function __construct(private QuestionBankRepository $banks) {}

    /** Upload form with the two import/template buttons. */
    public function form(Request $request, int $bankId): View
    {
        $bank    = $this->resolveBank($bankId);
        $history = QuestionImportBatch::query()
            ->where('question_bank_id', $bank->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('admin.question-banks.import.form', compact('bank', 'history'));
    }

    /** Streams the generated xlsx template. */
    public function template(int $bankId, GenerateQuestionImportTemplate $generator): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $bank    = $this->resolveBank($bankId);
        $tmpPath = $generator->execute($bank);

        return response()->download($tmpPath, 'question_bank_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Step 1 — parse the uploaded file, validate rows, persist preview, show preview view. */
    public function preview(Request $request, int $bankId, ParseQuestionsExcel $parser): View|RedirectResponse
    {
        $bank = $this->resolveBank($bankId);
        $user = $request->user();

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file   = $request->file('file');
        $stored = $file->store('question-imports/' . date('Y/m'), 'local');

        // Create batch record early so we can mark failed if parse throws
        $batch = QuestionImportBatch::create([
            'question_bank_id' => $bank->id,
            'school_id'        => $bank->school_id,
            'original_filename'=> $file->getClientOriginalName(),
            'stored_path'      => $stored,
            'status'           => 'previewed',
            'created_by'       => $user->id,
        ]);

        try {
            $rows = $parser->execute($file);
        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed']);

            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        if (empty($rows)) {
            $batch->update(['status' => 'failed']);

            return back()->withErrors(['file' => __('question_import.errors.empty_file')])->withInput();
        }

        $counts = $this->countStatuses($rows);

        $batch->update([
            'total_rows'   => count($rows),
            'preview_data' => $rows,   // cast to array by model
        ]);

        return view('admin.question-banks.import.preview', compact('bank', 'batch', 'rows', 'counts'));
    }

    /** Step 2 — insert valid rows, update batch, show result view. */
    public function execute(Request $request, int $bankId, int $batchId, ImportQuestionsAction $importer): View|RedirectResponse
    {
        $bank  = $this->resolveBank($bankId);
        $batch = $this->findOwnedBatch($batchId, $bank->id);

        if (! $batch) {
            return redirect()
                ->route('admin.question-banks.questions.import.form', $bankId)
                ->withErrors(['file' => __('question_import.errors.batch_missing')]);
        }

        $previewRows = $batch->preview_data ?? [];
        if (empty($previewRows)) {
            return redirect()
                ->route('admin.question-banks.questions.import.form', $bankId)
                ->withErrors(['file' => __('question_import.errors.no_preview')]);
        }

        $result = $importer->executeFromPreview($previewRows, $batch);

        return view('admin.question-banks.import.result', compact('bank', 'batch', 'result'));
    }

    /** Download error rows as a UTF-8 BOM CSV. */
    public function errorsReport(int $bankId, int $batchId): StreamedResponse|RedirectResponse
    {
        $bank  = $this->resolveBank($bankId);
        $batch = $this->findOwnedBatch($batchId, $bank->id);

        if (! $batch) {
            return redirect()->route('admin.question-banks.questions.import.form', $bankId);
        }

        $errors = QuestionImportError::where('import_batch_id', $batch->id)
            ->orderBy('row_number')
            ->get();

        $filename = 'question-import-errors-' . $batch->id . '.csv';

        return response()->streamDownload(function () use ($errors, $batch) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            $safe = fn ($v) => $this->csvSafe($v);
            fputcsv($out, array_map($safe, [
                __('question_import.result.col_row'),
                __('question_import.result.col_errors'),
                __('question_import.result.col_raw'),
            ]));
            foreach ($errors as $err) {
                fputcsv($out, array_map($safe, [
                    $err->row_number,
                    implode(' | ', $err->errors ?? []),
                    json_encode($err->raw ?? [], JSON_UNESCAPED_UNICODE),
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ───────────────────────────────────────────────────────────────────────
    // Helpers
    // ───────────────────────────────────────────────────────────────────────

    private function resolveBank(int $bankId): \App\Models\QuestionBank
    {
        $schoolId = $this->activeSchoolId();
        $bank     = $this->banks->findScoped($bankId, $schoolId);
        abort_if(! $bank, 404);

        return $bank;
    }

    private function findOwnedBatch(int $batchId, int $bankId): ?QuestionImportBatch
    {
        return QuestionImportBatch::where('id', $batchId)
            ->where('question_bank_id', $bankId)
            ->first();
    }

    private function countStatuses(array $rows): array
    {
        $c = ['valid' => 0, 'invalid' => 0];
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

        return preg_match('/^[=+\-@\t\r]/', $s) ? "'" . $s : $s;
    }
}
