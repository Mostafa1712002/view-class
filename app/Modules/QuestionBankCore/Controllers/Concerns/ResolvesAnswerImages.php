<?php

namespace App\Modules\QuestionBankCore\Controllers\Concerns;

use App\Models\QuestionBank;
use Illuminate\Http\Request;

/**
 * #247/#250 §10 — shared per-answer image handling for the question + passage
 * child-question forms. Resolves uploaded files (or kept existing paths on edit)
 * into index→path maps that MapAnswerData consumes. Only MCQ + matching carry
 * answer images (the columns answer_image / column_a_image / column_b_image).
 */
trait ResolvesAnswerImages
{
    /**
     * @param  array<string,mixed>  $data  validated payload (must carry `type`)
     * @return array<string,mixed>
     */
    protected function resolveAnswerImages(Request $request, QuestionBank $bank, array $data): array
    {
        $dir = 'bank-questions/'.$bank->id.'/answers';
        $type = $data['type'] ?? null;

        if ($type === 'mcq') {
            $data['option_image_paths'] = $this->mapAnswerImageGroup(
                $request, $dir, 'option_images', 'option_images_keep'
            );
        }
        if ($type === 'matching') {
            $data['matching_left_image_paths'] = $this->mapAnswerImageGroup(
                $request, $dir, 'matching_left_images', 'matching_left_images_keep'
            );
            $data['matching_right_image_paths'] = $this->mapAnswerImageGroup(
                $request, $dir, 'matching_right_images', 'matching_right_images_keep'
            );
        }

        return $data;
    }

    /**
     * Build [originalIndex => storedPath] for one answer-image group. A NEW upload
     * wins; otherwise a kept path (echoed by the edit form) is preserved so an
     * unchanged image survives the delete-and-recreate in syncAnswerRows().
     *
     * @return array<int,string>
     */
    protected function mapAnswerImageGroup(Request $request, string $dir, string $fileKey, string $keepKey): array
    {
        $files = $request->file($fileKey, []) ?: [];
        $keep  = (array) $request->input($keepKey, []);
        $count = max(
            $files ? (max(array_keys($files)) + 1) : 0,
            $keep ? (max(array_keys($keep)) + 1) : 0
        );

        $paths = [];
        for ($i = 0; $i < $count; $i++) {
            $file = $files[$i] ?? null;
            if ($file !== null) {
                $paths[$i] = $file->store($dir, 'public');
                continue;
            }
            $kept = trim((string) ($keep[$i] ?? ''));
            if ($kept !== '') {
                $paths[$i] = $kept;
            }
        }

        return $paths;
    }
}
