<?php

namespace App\Modules\QuestionBankCore\Actions\Import;

use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportError;
use App\Modules\QuestionBankCore\Actions\CreateQuestion;
use App\Modules\QuestionBankCore\Models\Passage;
use Illuminate\Support\Facades\DB;

/**
 * #254 — confirm step. Saves the VALID preview rows by routing each through the
 * existing CreateQuestion action so every imported question writes BOTH the
 * bank_questions row AND the normalized question_answers rows (+ answer_data JSON
 * via MapAnswerData) — exactly like a manual create. Invalid/duplicate rows are
 * skipped and recorded in question_import_errors. Updates the batch counters.
 * Passage rows are not questions: the Passage is created with its first imported
 * sub-question and each sub-question is attached in sheet order (passage_questions).
 */
final class ImportFromPreview
{
    public function __construct(private CreateQuestion $createQuestion, private ResolveImportImage $images) {}

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
        $passageRows = collect($previewRows)->filter(fn ($r) => ! empty($r['is_passage']))->keyBy('rowNumber')->all();
        $passages = []; // passage rowNumber => created Passage

        $batch->update(['status' => 'pending', 'started_at' => now()]);

        foreach ($previewRows as $pr) {
            $rowStatus = $pr['status'] ?? 'valid';

            if (! empty($pr['is_passage'])) {
                if ($rowStatus !== 'valid') {
                    $failed++;
                    $this->recordError($batch, $pr);
                }

                continue;
            }

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
                $passageRow = $pr['passage_row'] ?? null;
                $passage = DB::transaction(function () use ($bank, $batch, $pr, $statusChoice, $importedBy, $passageRow, $passageRows, $passages) {
                    $data = $this->buildPayload($bank, $pr, $statusChoice);
                    $data = $this->resolveImages($bank, $pr, $data);
                    $passage = $passageRow ? ($passages[$passageRow] ?? $this->createPassage($bank, $passageRows[$passageRow], $importedBy)) : null;
                    $data['passage_id'] = $passage?->id;
                    $question = $this->createQuestion->execute($bank, $data);
                    $passage?->questions()->attach($question->id, ['sort_order' => (int) $pr['passage_sort']]);
                    // Tag provenance (CreateQuestion defaults source=manual).
                    $question->update([
                        'source' => 'imported',
                        'import_batch_id' => $batch->id,
                        'imported_by' => $importedBy,
                    ]);

                    return $passage;
                });
                if ($passage) {
                    $passages[$passageRow] = $passage; // only after commit, so a rolled-back row never leaves a stale id
                }
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
            'passages' => count($passages),
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
            // attachment_path / option_image_paths are filled by resolveImages().
            'attachment_path' => null,
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
     * Mirrors PassageController::store (status rule included).
     *
     * @param  array<string,mixed>  $row  parsed passage row
     */
    private function createPassage(QuestionBank $bank, array $row, ?int $importedBy): Passage
    {
        return Passage::create([
            'question_bank_id' => $bank->id,
            'passage_code' => $row['passage_code'] ?? null,
            'passage_text' => $row['passage_text'] ?? '',
            'passage_image' => ! empty($row['attachment_url']) ? $this->images->execute($row['attachment_url'], $bank->id) : null,
            'subject_id' => $row['subject_id'] ?? null,
            'semester_id' => $row['semester_id'] ?? null,
            'week_id' => $row['week_id'] ?? null,
            'skill_id' => $row['skill_id'] ?? null,
            'difficulty_level' => $row['difficulty'] ?? 1,
            'status' => $bank->requires_approval ? 'draft' : 'approved',
            'created_by' => $importedBy,
        ]);
    }

    /**
     * Store the referenced images on the public disk and point the payload at them
     * (attachment_path / option_image_paths), so they render like uploaded ones.
     *
     * @param  array<string,mixed>  $pr
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function resolveImages(QuestionBank $bank, array $pr, array $data): array
    {
        if (! empty($pr['attachment_url'])) {
            $data['attachment_path'] = $this->images->execute($pr['attachment_url'], $bank->id);
        }
        foreach ($pr['option_images'] ?? [] as $i => $ref) {
            $data['option_image_paths'][(int) $i] = $this->images->execute($ref, $bank->id);
        }

        return $data;
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
