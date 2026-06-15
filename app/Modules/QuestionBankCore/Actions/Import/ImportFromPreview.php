<?php

namespace App\Modules\QuestionBankCore\Actions\Import;

use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportError;
use App\Modules\QuestionBankCore\Actions\CreateQuestion;
use Illuminate\Support\Facades\DB;

/**
 * #254 — confirm step. Saves the VALID preview rows by routing each through the
 * existing CreateQuestion action so every imported question writes BOTH the
 * bank_questions row AND the normalized question_answers rows (+ answer_data JSON
 * via MapAnswerData) — exactly like a manual create. Invalid/duplicate rows are
 * skipped and recorded in question_import_errors. Updates the batch counters.
 */
final class ImportFromPreview
{
    public function __construct(private CreateQuestion $createQuestion) {}

    /**
     * @param  array<int,array<string,mixed>>  $previewRows
     * @param  string  $statusChoice  draft|pending_review|approved (user choice)
     * @param  string  $duplicatePolicy  skip|update|new
     */
    public function execute(
        QuestionBank $bank,
        QuestionImportBatch $batch,
        array $previewRows,
        string $statusChoice,
        string $duplicatePolicy,
        ?int $importedBy
    ): object {
        $imported = 0;
        $failed = 0;

        $batch->update(['status' => 'pending', 'started_at' => now()]);

        foreach ($previewRows as $pr) {
            $rowStatus = $pr['status'] ?? 'valid';

            // Duplicate rows: honour the policy. Only skip|new are supported (the
            // request rejects "update"). policy=new turns a duplicate into a fresh
            // copy by dropping the colliding code; otherwise it is recorded as error.
            if ($rowStatus === 'duplicate' && $duplicatePolicy === 'new') {
                $pr['question_code'] = null; // create a fresh copy without the colliding code
                $rowStatus = 'valid';
            }

            if ($rowStatus !== 'valid') {
                $failed++;
                $this->recordError($batch, $pr);

                continue;
            }

            try {
                DB::transaction(function () use ($bank, $batch, $pr, $statusChoice, $importedBy) {
                    $data = $this->buildPayload($bank, $pr, $statusChoice);
                    $question = $this->createQuestion->execute($bank, $data);
                    // Tag provenance (CreateQuestion defaults source=manual).
                    $question->update([
                        'source' => 'imported',
                        'import_batch_id' => $batch->id,
                        'imported_by' => $importedBy,
                    ]);
                });
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $pr['errors'] = array_merge($pr['errors'] ?? [], [$e->getMessage()]);
                $this->recordError($batch, $pr);
            }
        }

        $batch->update([
            'status' => 'completed',
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'finished_at' => now(),
        ]);

        // Update the bank's cached question count if such a column exists.
        if (\Schema::hasColumn('question_banks', 'questions_count')) {
            $bank->update(['questions_count' => BankQuestion::where('question_bank_id', $bank->id)->count()]);
        }

        return (object) [
            'total' => count($previewRows),
            'imported' => $imported,
            'failed' => $failed,
            'status' => 'completed',
        ];
    }

    /**
     * Build the CreateQuestion payload from a parsed row, clamping status to the
     * caller's permission (mirrors QuestionController::resolveCreateStatus).
     *
     * @param  array<string,mixed>  $pr
     * @return array<string,mixed>
     */
    private function buildPayload(QuestionBank $bank, array $pr, string $statusChoice): array
    {
        $status = $this->clampStatus($bank, $statusChoice);

        return [
            'question_category' => $pr['question_category'] ?? 'normal',
            'subject_id' => $pr['subject_id'] ?? null,
            'grade_id' => $pr['grade_id'] ?? null,
            'class_id' => $pr['class_id'] ?? null,
            'semester_id' => $pr['semester_id'] ?? null,
            'week_id' => $pr['week_id'] ?? null,
            'skill_id' => $pr['skill_id'] ?? null,
            'type' => $pr['type'],
            'question_code' => $pr['question_code'] ?? null,
            'question_content_type' => $pr['question_content_type'] ?? 'text',
            'is_full_image_question' => (bool) ($pr['is_full_image_question'] ?? false),
            'body_ar' => $pr['body_ar'] ?? null,
            'explanation' => $pr['explanation'] ?? null,
            'difficulty' => $pr['difficulty'] ?? 1,
            'points' => $pr['points'] ?? 1,
            // Image import (Method 1 URL / Method 2 ZIP) is UNVERIFIED: the raw URL
            // is stored verbatim in attachment_path (normally a public-disk relative
            // path), so display of imported images is not guaranteed. See spec note.
            'attachment_path' => $pr['attachment_url'] ?? null,
            'status' => $status,
            // answer inputs consumed by MapAnswerData
            'options_ar' => $pr['options_ar'] ?? [],
            'correct_index' => $pr['correct_index'] ?? null,
            'correct' => $pr['correct'] ?? null,
            'short_answer' => $pr['short_answer'] ?? null,
            'essay_answer' => $pr['essay_answer'] ?? null,
            'blanks' => $pr['blanks'] ?? [],
            'matching_left' => $pr['matching_left'] ?? [],
            'matching_right' => $pr['matching_right'] ?? [],
        ];
    }

    /**
     * Clamp a requested import status to one the importer is allowed to set.
     * `approved` needs question_banks.approve OR an auto-approve bank.
     */
    private function clampStatus(QuestionBank $bank, string $requested): string
    {
        if (! in_array($requested, BankQuestion::STATUSES, true)) {
            $requested = $bank->requires_approval ? BankQuestion::STATUS_PENDING_REVIEW : BankQuestion::STATUS_APPROVED;
        }
        $user = auth()->user();
        if ($requested === BankQuestion::STATUS_APPROVED
            && ! (($user?->canDo('question_banks.approve') ?? false) || ! $bank->requires_approval)) {
            return BankQuestion::STATUS_PENDING_REVIEW;
        }

        return $requested;
    }

    /**
     * @param  array<string,mixed>  $pr
     */
    private function recordError(QuestionImportBatch $batch, array $pr): void
    {
        $errors = $pr['errors'] ?? [];
        QuestionImportError::create([
            'import_batch_id' => $batch->id,
            'row_number' => $pr['rowNumber'] ?? 0,
            'question_code' => $pr['question_code'] ?? null,
            'errors' => $errors,
            'error_message' => implode(' | ', $errors),
            'error_type' => ($pr['status'] ?? 'invalid') === 'duplicate' ? 'duplicate' : 'validation',
            'raw' => $pr['raw'] ?? [],
        ]);
    }
}
