<?php

namespace App\Modules\QuestionBankCore\Actions;

use App\Models\ActivityLog;
use App\Models\BankQuestion;
use App\Models\QuestionBank;
use Illuminate\Support\Facades\DB;

/**
 * Creates a normal (عادي) bank question (#250). Persists to bank_questions, the
 * normalized question_answers table, AND the legacy answer_data JSON (kept in
 * sync so legacy views/exams keep working). Writes an activity-log entry.
 */
final class CreateQuestion
{
    public function __construct(private MapAnswerData $mapAnswers) {}

    /**
     * @param  array<string,mixed>  $data  validated payload (incl. resolved
     *                                      attachment_path + content_type)
     */
    public function execute(QuestionBank $bank, array $data): BankQuestion
    {
        $answers = $this->mapAnswers->execute($data);

        return DB::transaction(function () use ($bank, $data, $answers) {
            $question = $bank->questions()->create([
                'question_category'     => $data['question_category'] ?? 'normal',
                'subject_id'            => $data['subject_id'] ?? null,
                'grade_id'              => $data['grade_id'] ?? null,
                'class_id'              => $data['class_id'] ?? null,
                'semester_id'           => $data['semester_id'] ?? null,
                'week_id'               => $data['week_id'] ?? null,
                'skill_id'              => $data['skill_id'] ?? null,
                'standard_id'           => $data['standard_id'] ?? null,
                'lesson_id'             => $data['lesson_id'] ?? null,
                'passage_id'            => $data['passage_id'] ?? null,
                'type'                  => $data['type'],
                'question_code'         => $data['question_code'] ?? null,
                'question_content_type' => $data['question_content_type'],
                'is_full_image_question' => (bool) ($data['is_full_image_question'] ?? false),
                // body_ar is NOT NULL in the schema; a full-image question legitimately
                // has no text, so coalesce null → '' instead of crashing on insert.
                'body_ar'               => $data['body_ar'] ?? '',
                'body_en'               => $data['body_en'] ?? null,
                'explanation'           => $data['explanation'] ?? null,
                'answer_data'           => $answers['json'],
                'difficulty'            => $data['difficulty'] ?? 1,
                'points'                => $data['points'] ?? 1,
                'attachment_path'       => $data['attachment_path'] ?? null,
                'source'                => 'manual',
                'status'                => $data['status'] ?? 'approved',
                'created_by'            => auth()->id(),
            ]);

            $this->syncAnswerRows($question, $answers['rows']);

            ActivityLog::log(
                'question_banks.create',
                "إضافة سؤال جديد في بنك {$bank->name_ar} (#{$question->id})",
                $question,
                null,
                $question->only(['type', 'question_code', 'status', 'points'])
            );

            return $question;
        });
    }

    /**
     * Replace the normalized answer rows for a question.
     *
     * @param  array<int,array<string,mixed>>  $rows
     */
    public function syncAnswerRows(BankQuestion $question, array $rows): void
    {
        $question->answers()->delete();
        foreach ($rows as $row) {
            $question->answers()->create($row);
        }
    }
}
