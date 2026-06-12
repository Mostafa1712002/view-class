<?php

namespace App\Modules\QuestionBanks\Actions;

use Illuminate\Http\UploadedFile;

/**
 * Parses an uploaded question_bank_import_template.xlsx into row arrays.
 *
 * Each returned row array contains the parsed fields plus:
 *   - rowNumber: original Excel row number (starts at 2 because row 1 is the header)
 *   - status: 'valid' | 'invalid'
 *   - errors: array of Arabic error messages for invalid rows
 *
 * Supported types: xlsx, xls (CSV is not expected for this template but handled).
 */
final class ParseQuestionsExcel
{
    /** Normalized header => internal key */
    private const HEADER_MAP = [
        'question_code'         => 'question_code',
        'question_type'         => 'question_type',
        'question_content_type' => 'question_content_type',
        'question_text'         => 'question_text',
        'option_a'              => 'option_a',
        'option_b'              => 'option_b',
        'option_c'              => 'option_c',
        'option_d'              => 'option_d',
        'correct_answer'        => 'correct_answer',
        'difficulty'            => 'difficulty',
        'explanation'           => 'explanation',
        'grade'                 => 'grade',
        'semester'              => 'semester',
    ];

    private const ALLOWED_TYPES = ['mcq', 'true_false', 'short', 'essay', 'matching', 'fill_blank'];

    private const ALLOWED_CONTENT_TYPES = ['text', 'image', 'mixed'];

    private const DIFFICULTY_MAP = [
        'سهل'   => 1,
        'easy'   => 1,
        '1'      => 1,
        'متوسط' => 2,
        'medium' => 2,
        '2'      => 2,
        'صعب'   => 3,
        'hard'   => 3,
        '3'      => 3,
    ];

    /**
     * @return array<int, array<string, mixed>>  One array per data row.
     *
     * @throws \RuntimeException|\InvalidArgumentException on unreadable/wrong-format files.
     */
    public function execute(UploadedFile $file): array
    {
        $ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->getRealPath();

        $matrix = match (true) {
            in_array($ext, ['csv', 'txt'], true) => $this->readCsv($path),
            in_array($ext, ['xlsx', 'xls'], true) => $this->readXlsx($path),
            default => throw new \InvalidArgumentException(__('question_import.errors.invalid_file')),
        };

        if (empty($matrix)) {
            return [];
        }

        // First row is the header
        $rawHeader = array_shift($matrix);
        $header    = array_map(fn ($v) => $this->normalizeHeader((string) ($v ?? '')), $rawHeader);
        $colIndex  = $this->buildColumnIndex($header);

        // Require at minimum: question_type and (question_text or question_code)
        foreach (['question_type'] as $req) {
            if (! array_key_exists($req, $colIndex)) {
                throw new \RuntimeException(__('question_import.errors.bad_format'));
            }
        }

        $rows      = [];
        $rowNumber = 1; // header was row 1
        foreach ($matrix as $raw) {
            $rowNumber++;
            if ($this->isEmptyRow($raw)) {
                continue;
            }
            $rows[] = $this->buildRow($rowNumber, $colIndex, $raw);
        }

        return $rows;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException(__('question_import.errors.parse_failed'));
        }
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $matrix = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $matrix[] = $row;
        }
        fclose($handle);

        return $matrix;
    }

    private function readXlsx(string $path): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException(__('question_import.errors.missing_library'));
        }
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        // Read only the first sheet ("Questions")
        return $spreadsheet->getSheet(0)->toArray(null, true, true, false);
    }

    /** @return array<string,int> internal-key => column index */
    private function buildColumnIndex(array $header): array
    {
        $map = [];
        foreach ($header as $idx => $name) {
            if ($name === '') {
                continue;
            }
            if (isset(self::HEADER_MAP[$name]) && ! isset($map[self::HEADER_MAP[$name]])) {
                $map[self::HEADER_MAP[$name]] = $idx;
            }
        }

        return $map;
    }

    private function buildRow(int $rowNumber, array $colIndex, array $raw): array
    {
        $get = function (string $key) use ($colIndex, $raw): ?string {
            if (! array_key_exists($key, $colIndex)) {
                return null;
            }
            $v = $raw[$colIndex[$key]] ?? null;
            if ($v === null) {
                return null;
            }
            $v = trim((string) $v);

            return $v === '' ? null : $v;
        };

        $row = [
            'rowNumber'            => $rowNumber,
            'question_code'        => $get('question_code'),
            'question_type'        => $get('question_type'),
            'question_content_type'=> $get('question_content_type') ?? 'text',
            'question_text'        => $get('question_text'),
            'option_a'             => $get('option_a'),
            'option_b'             => $get('option_b'),
            'option_c'             => $get('option_c'),
            'option_d'             => $get('option_d'),
            'correct_answer'       => $get('correct_answer'),
            'difficulty_raw'       => $get('difficulty'),
            'explanation'          => $get('explanation'),
            'grade'                => $get('grade'),
            'semester'             => $get('semester'),
            'status'               => 'valid',
            'errors'               => [],
        ];

        // Validate and map fields
        $errors = [];

        // question_type
        $type = strtolower(trim((string) ($row['question_type'] ?? '')));
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            $errors[] = __('question_import.validation.invalid_type', ['value' => $row['question_type'] ?? '']);
        } else {
            $row['question_type'] = $type;
        }

        // question_content_type
        $contentType = strtolower(trim((string) ($row['question_content_type'] ?? 'text')));
        if (! in_array($contentType, self::ALLOWED_CONTENT_TYPES, true)) {
            $errors[] = __('question_import.validation.invalid_content_type', ['value' => $contentType]);
        } else {
            $row['question_content_type'] = $contentType;
        }

        // question_text — required unless full_image (content_type=image + code present)
        $isFullImage = ($contentType === 'image' && ! empty($row['question_code']));
        if (! $isFullImage && empty($row['question_text'])) {
            $errors[] = __('question_import.validation.missing_text');
        }

        // difficulty
        $diffRaw = strtolower(trim((string) ($row['difficulty_raw'] ?? '')));
        if ($diffRaw !== '' && ! isset(self::DIFFICULTY_MAP[$diffRaw])) {
            $errors[] = __('question_import.validation.invalid_difficulty', ['value' => $row['difficulty_raw']]);
            $row['difficulty'] = 1;
        } else {
            $row['difficulty'] = self::DIFFICULTY_MAP[$diffRaw] ?? 1;
        }

        // correct_answer — required for mcq and true_false
        if (in_array($type, ['mcq', 'true_false'], true) && empty($row['correct_answer'])) {
            $errors[] = __('question_import.validation.missing_correct_answer');
        }

        // Build answer_data structure
        $row['answer_data'] = $this->buildAnswerData($type, $row);

        if (! empty($errors)) {
            $row['status'] = 'invalid';
            $row['errors'] = $errors;
        }

        return $row;
    }

    private function buildAnswerData(string $type, array $row): ?array
    {
        switch ($type) {
            case 'mcq':
                $options = array_values(array_filter([
                    $row['option_a'],
                    $row['option_b'],
                    $row['option_c'],
                    $row['option_d'],
                ], fn ($v) => $v !== null && $v !== ''));
                $correctLetter = strtoupper(trim((string) ($row['correct_answer'] ?? '')));
                $correctIndex  = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3][$correctLetter] ?? null;

                return ['options' => $options, 'correct' => $correctIndex];

            case 'true_false':
                $val = strtolower(trim((string) ($row['correct_answer'] ?? '')));

                return ['correct' => in_array($val, ['true', '1', 'صح', 'صحيح'], true) ? 'true' : 'false'];

            case 'short':
                return ['model_answer' => $row['correct_answer'] ?? null];

            case 'essay':
                return ['model_answer' => $row['correct_answer'] ?? null];

            case 'fill_blank':
                $ans = $row['correct_answer'] ?? '';

                return ['blanks' => array_filter(explode('|', $ans), fn ($v) => trim($v) !== '')];

            case 'matching':
                // Not fully supported via simple template; leave empty
                return ['pairs' => []];
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        $value = str_replace('*', '', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return strtolower(trim($value));
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
