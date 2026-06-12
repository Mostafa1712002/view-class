<?php

namespace App\Modules\QuestionBanks\Actions;

use App\Models\BankQuestion;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportError;
use Illuminate\Support\Facades\DB;

/**
 * Applies parsed + validated preview rows to bank_questions.
 *
 * Invalid rows are skipped (counted as failed).
 * Each valid row is wrapped in its own DB::transaction so a single
 * row failure does not roll back the whole import.
 *
 * Returns a plain result value object.
 */
final class ImportQuestionsAction
{
    /**
     * @param  array<int, array<string, mixed>>  $previewRows
     */
    public function executeFromPreview(array $previewRows, QuestionImportBatch $batch): object
    {
        $imported = 0;
        $failed   = 0;

        foreach ($previewRows as $pr) {
            if (($pr['status'] ?? '') === 'invalid') {
                $failed++;
                // Write to question_import_errors
                QuestionImportError::create([
                    'import_batch_id' => $batch->id,
                    'row_number'      => $pr['rowNumber'] ?? 0,
                    'errors'          => $pr['errors'] ?? [],
                    'raw'             => $this->safeRaw($pr),
                ]);
                continue;
            }

            try {
                DB::transaction(function () use ($pr, $batch) {
                    BankQuestion::create([
                        'question_bank_id'      => $batch->question_bank_id,
                        'question_code'         => $pr['question_code'] ?? null,
                        'question_content_type' => $pr['question_content_type'] ?? 'text',
                        'is_full_image_question'=> ($pr['question_content_type'] ?? 'text') === 'image'
                                                   && ! empty($pr['question_code']),
                        'type'                  => $pr['question_type'] ?? 'mcq',
                        'body_ar'               => $pr['question_text'] ?? '',
                        'answer_data'           => $pr['answer_data'] ?? null,
                        'difficulty'            => $pr['difficulty'] ?? 1,
                        'points'                => 1,
                        'explanation'           => $pr['explanation'] ?? null,
                        'source'                => 'imported',
                        'imported_by'           => $batch->created_by,
                        'import_batch_id'       => $batch->id,
                        'status'                => 'draft',
                        'created_by'            => $batch->created_by,
                    ]);
                });
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                QuestionImportError::create([
                    'import_batch_id' => $batch->id,
                    'row_number'      => $pr['rowNumber'] ?? 0,
                    'errors'          => [$e->getMessage()],
                    'raw'             => $this->safeRaw($pr),
                ]);
            }
        }

        // Update batch counters
        $batch->update([
            'status'        => 'completed',
            'total_rows'    => count($previewRows),
            'imported_rows' => $imported,
            'failed_rows'   => $failed,
        ]);

        return (object) [
            'total'    => count($previewRows),
            'imported' => $imported,
            'failed'   => $failed,
            'status'   => 'completed',
        ];
    }

    /** Strip large/sensitive keys before storing as raw in error table. */
    private function safeRaw(array $pr): array
    {
        $skip = ['answer_data', 'errors', 'status'];
        $out  = [];
        foreach ($pr as $k => $v) {
            if (! in_array($k, $skip, true)) {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
