<?php

namespace App\Modules\QuestionBankCore\Actions;

/**
 * Maps validated per-type answer input into BOTH representations:
 *   - the legacy bank_questions.answer_data JSON (so legacy views/exams keep working)
 *   - normalized question_answers rows (the new #258 store)
 *
 * The JSON shapes are byte-for-byte compatible with the legacy
 * BankQuestionController::extractAnswerData() so existing readers don't break.
 */
final class MapAnswerData
{
    /**
     * @param  array<string,mixed>  $data  validated request data
     * @return array{json: array|null, rows: array<int,array<string,mixed>>}
     */
    public function execute(array $data): array
    {
        return match ($data['type']) {
            'mcq'        => $this->mcq($data),
            'true_false' => $this->trueFalse($data),
            'essay'      => $this->modelAnswer($data, 'essay_answer'),
            'short'      => $this->modelAnswer($data, 'short_answer'),
            'matching'   => $this->matching($data),
            'fill_blank' => $this->fillBlank($data),
            default      => ['json' => null, 'rows' => []],
        };
    }

    private function mcq(array $data): array
    {
        $options = array_values(array_filter(
            $data['options_ar'] ?? [],
            static fn ($v) => $v !== null && $v !== ''
        ));
        $correctRaw = $data['correct_index'] ?? $data['correct'] ?? null;
        $correct = is_numeric($correctRaw) ? (int) $correctRaw : null;

        $rows = [];
        foreach ($options as $i => $text) {
            $rows[] = [
                'answer_text'         => $text,
                'answer_content_type' => 'text',
                'is_correct'          => $correct === $i,
                'sort_order'          => $i,
            ];
        }

        return [
            'json' => ['options' => $options, 'correct' => $correct],
            'rows' => $rows,
        ];
    }

    private function trueFalse(array $data): array
    {
        // Legacy stores correct as the raw value ('true'/'false' or 1/0).
        $correct = $data['correct'] ?? null;
        $isTrue = in_array($correct, [true, 'true', '1', 1], true);

        $rows = [
            ['answer_text' => 'صح',  'answer_content_type' => 'text', 'is_correct' => $isTrue,  'sort_order' => 0],
            ['answer_text' => 'خطأ', 'answer_content_type' => 'text', 'is_correct' => ! $isTrue, 'sort_order' => 1],
        ];

        return ['json' => ['correct' => $correct], 'rows' => $rows];
    }

    private function modelAnswer(array $data, string $field): array
    {
        $answer = $data[$field] ?? null;

        $rows = [];
        if ($answer !== null && $answer !== '') {
            $rows[] = [
                'answer_text'         => $answer,
                'answer_content_type' => 'text',
                'is_correct'          => true,
                'sort_order'          => 0,
            ];
        }

        return ['json' => ['model_answer' => $answer], 'rows' => $rows];
    }

    private function matching(array $data): array
    {
        $left = $data['matching_left'] ?? [];
        $right = $data['matching_right'] ?? [];
        $count = max(count($left), count($right));

        $pairs = [];
        $rows = [];
        $sort = 0;
        for ($i = 0; $i < $count; $i++) {
            $l = trim((string) ($left[$i] ?? ''));
            $r = trim((string) ($right[$i] ?? ''));
            if ($l === '' || $r === '') {
                continue;
            }
            $pairs[] = ['left' => $l, 'right' => $r];
            $rows[] = [
                'answer_content_type' => 'text',
                'is_correct'          => true,
                'sort_order'          => $sort++,
                'column_a_text'       => $l,
                'column_b_text'       => $r,
            ];
        }

        return ['json' => ['pairs' => $pairs], 'rows' => $rows];
    }

    private function fillBlank(array $data): array
    {
        $blanks = array_values(array_filter(
            $data['blanks'] ?? [],
            static fn ($v) => $v !== null && $v !== ''
        ));

        $rows = [];
        foreach ($blanks as $i => $answer) {
            $rows[] = [
                'answer_text'         => $answer,
                'answer_content_type' => 'text',
                'is_correct'          => true,
                'sort_order'          => $i,
                'blank_number'        => $i + 1,
            ];
        }

        return ['json' => ['blanks' => $blanks], 'rows' => $rows];
    }
}
