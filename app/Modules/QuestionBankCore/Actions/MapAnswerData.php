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
        // An option is kept if it has text OR a resolved image path. The resolved
        // image paths (index → stored path) are supplied by the controller and
        // keyed by the ORIGINAL option index, so we walk the original indexes and
        // re-pack into a dense list — keeping options + correct aligned.
        $texts = $data['options_ar'] ?? [];
        $imagePaths = $data['option_image_paths'] ?? []; // [origIndex => path]
        $correctRaw = $data['correct_index'] ?? $data['correct'] ?? null;
        $correctOrig = is_numeric($correctRaw) ? (int) $correctRaw : null;

        $count = max(
            count($texts),
            $imagePaths ? (max(array_keys($imagePaths)) + 1) : 0
        );

        $options = [];   // legacy JSON: option text (kept for backward compat)
        $rows = [];
        $newCorrect = null;
        $dense = 0;
        for ($i = 0; $i < $count; $i++) {
            $text = isset($texts[$i]) ? trim((string) $texts[$i]) : '';
            $image = $imagePaths[$i] ?? null;
            if ($text === '' && ! $image) {
                continue; // empty option — drop
            }
            if ($correctOrig === $i) {
                $newCorrect = $dense;
            }
            $options[] = $text;
            $rows[] = [
                'answer_text'         => $text !== '' ? $text : null,
                'answer_image'        => $image,
                'answer_content_type' => $this->contentType($text, $image),
                'is_correct'          => $correctOrig === $i,
                'sort_order'          => $dense,
            ];
            $dense++;
        }

        return [
            'json' => ['options' => $options, 'correct' => $newCorrect],
            'rows' => $rows,
        ];
    }

    /**
     * Resolve the answer_content_type enum from what the answer actually carries.
     * DB enum is ('text','image','text_and_image') — note NOT 'mixed'.
     */
    private function contentType(string $text, ?string $image): string
    {
        if ($image && $text !== '') {
            return 'text_and_image';
        }
        if ($image) {
            return 'image';
        }

        return 'text';
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
        $leftImages = $data['matching_left_image_paths'] ?? [];   // [origIndex => path]
        $rightImages = $data['matching_right_image_paths'] ?? [];
        $count = max(
            count($left),
            count($right),
            $leftImages ? (max(array_keys($leftImages)) + 1) : 0,
            $rightImages ? (max(array_keys($rightImages)) + 1) : 0
        );

        $pairs = [];
        $rows = [];
        $sort = 0;
        for ($i = 0; $i < $count; $i++) {
            $l = trim((string) ($left[$i] ?? ''));
            $r = trim((string) ($right[$i] ?? ''));
            $li = $leftImages[$i] ?? null;
            $ri = $rightImages[$i] ?? null;
            // A side is present if it has text OR an image.
            if (($l === '' && ! $li) || ($r === '' && ! $ri)) {
                continue;
            }
            $pairs[] = ['left' => $l, 'right' => $r];
            $rows[] = [
                'answer_content_type' => $this->contentType($l !== '' ? $l : $r, $li ?? $ri),
                'is_correct'          => true,
                'sort_order'          => $sort++,
                'column_a_text'       => $l !== '' ? $l : null,
                'column_a_image'      => $li,
                'column_b_text'       => $r !== '' ? $r : null,
                'column_b_image'      => $ri,
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
