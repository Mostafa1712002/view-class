<?php

namespace App\Modules\QuestionBankCore\Actions;

use App\Models\ActivityLog;
use App\Models\BankQuestion;
use Illuminate\Support\Facades\DB;

/**
 * Updates a normal bank question (#250). Keeps question_answers and the legacy
 * answer_data JSON in sync, and logs the change.
 */
final class UpdateQuestion
{
    public function __construct(
        private MapAnswerData $mapAnswers,
        private CreateQuestion $create, // reuse syncAnswerRows()
    ) {}

    /**
     * @param  array<string,mixed>  $data  validated payload
     */
    public function execute(BankQuestion $question, array $data): BankQuestion
    {
        $answers = $this->mapAnswers->execute($data);
        $old = $question->only(['type', 'question_code', 'status', 'points', 'body_ar']);

        return DB::transaction(function () use ($question, $data, $answers, $old) {
            $question->update([
                'subject_id'            => $data['subject_id'] ?? null,
                'grade_id'              => $data['grade_id'] ?? null,
                'class_id'              => $data['class_id'] ?? null,
                'semester_id'           => $data['semester_id'] ?? null,
                'week_id'               => $data['week_id'] ?? null,
                'skill_id'              => $data['skill_id'] ?? null,
                'lesson_id'             => $data['lesson_id'] ?? null,
                'type'                  => $data['type'],
                'question_code'         => $data['question_code'] ?? null,
                'question_content_type' => $data['question_content_type'],
                'is_full_image_question' => (bool) ($data['is_full_image_question'] ?? false),
                'body_ar'               => $data['body_ar'] ?? null,
                'body_en'               => $data['body_en'] ?? null,
                'explanation'           => $data['explanation'] ?? null,
                'answer_data'           => $answers['json'],
                'difficulty'            => $data['difficulty'] ?? 1,
                'points'                => $data['points'] ?? 1,
                'attachment_path'       => $data['attachment_path'] ?? null,
                'status'                => $data['status'] ?? 'approved',
            ]);

            $this->create->syncAnswerRows($question, $answers['rows']);

            ActivityLog::log(
                'question_banks.edit',
                "تعديل سؤال (#{$question->id})",
                $question,
                $old,
                $question->only(['type', 'question_code', 'status', 'points', 'body_ar'])
            );

            return $question;
        });
    }
}
