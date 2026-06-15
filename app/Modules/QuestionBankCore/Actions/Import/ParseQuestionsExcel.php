<?php

namespace App\Modules\QuestionBankCore\Actions\Import;

use App\Models\AcademicTerm;
use App\Models\BankQuestion;
use App\Models\ClassRoom;
use App\Models\QuestionBank;
use App\Models\StudyWeek;
use App\Models\Subject;
use App\Modules\QuestionBankCore\Models\Skill;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * #254 — parses the QB-rebuild import xlsx (Questions sheet) into normalized rows
 * and runs the full validation matrix from the card BEFORE any DB write.
 *
 * Each returned row carries:
 *   - rowNumber        : 1-based Excel row (data starts at 2)
 *   - status           : valid | invalid | duplicate
 *   - errors           : Arabic messages for invalid/duplicate rows
 *   - raw              : original cell values (for the error report)
 *   - + the resolved payload keys consumed by MapAnswerData/CreateQuestion:
 *       type, question_category, question_code, body_ar, explanation, points,
 *       difficulty, question_content_type, is_full_image_question, attachment_url,
 *       subject_id, grade_id, class_id, semester_id, week_id, skill_id,
 *       options_ar[], correct_index, correct, short_answer, essay_answer,
 *       blanks[], matching_left[], matching_right[]
 *
 * Image support: the parser records *_image URLs (Method 1 — links) and validates
 * extension/URL shape. ZIP image bundles (Method 2) are NOT consumed here — see the
 * real-vs-stub note in the spec.
 */
final class ParseQuestionsExcel
{
    private const ALLOWED_TYPES = ['mcq', 'true_false', 'short', 'essay', 'matching', 'fill_blank'];

    private const ALLOWED_CATEGORIES = ['normal', 'tahsili', 'passage'];

    private const ALLOWED_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    private const DIFFICULTY_MAP = [
        'سهل' => 1, 'easy' => 1, '1' => 1,
        'متوسط' => 2, 'medium' => 2, '2' => 2,
        'صعب' => 3, 'hard' => 3, '3' => 3,
    ];

    /** Lookup caches (name → id) keyed within the bank's scope. */
    private array $subjects = [];

    private array $skills = [];

    private array $semesters = [];

    private array $weeks = [];

    private array $gradeLevels = [];

    private array $classes = [];

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException on unreadable / wrong-format files.
     */
    public function execute(UploadedFile $file, QuestionBank $bank, ?int $schoolId): array
    {
        $this->loadLookups($bank, $schoolId);

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Questions') ?? $spreadsheet->getSheet(0);

        $rows = $sheet->toArray(null, true, true, true); // assoc by column letter
        if (count($rows) < 2) {
            return [];
        }

        // Header row (row 1) → map column-letter → normalized header name.
        $headerRow = $rows[1] ?? [];
        $colByHeader = [];
        foreach ($headerRow as $letter => $value) {
            $name = $this->norm($value);
            if ($name !== '') {
                $colByHeader[$name] = $letter;
            }
        }

        $required = ['question_type', 'question_text', 'correct_answer'];
        $missingCols = array_values(array_diff($required, array_keys($colByHeader)));

        // Codes already present in the bank (duplicate-in-bank check).
        $existingCodes = BankQuestion::query()
            ->where('question_bank_id', $bank->id)
            ->whereNotNull('question_code')
            ->pluck('question_code')
            ->map(fn ($c) => mb_strtolower(trim((string) $c)))
            ->all();

        $seenInFile = [];
        $out = [];

        foreach ($rows as $rowNum => $cells) {
            if ($rowNum === 1) {
                continue; // header
            }
            // Skip fully empty rows.
            if (! $this->rowHasData($cells)) {
                continue;
            }

            $get = fn (string $header) => isset($colByHeader[$header])
                ? trim((string) ($cells[$colByHeader[$header]] ?? ''))
                : '';

            $raw = [];
            foreach ($colByHeader as $name => $letter) {
                $raw[$name] = trim((string) ($cells[$letter] ?? ''));
            }

            $errors = [];

            // Missing required columns is a file-level fault but reported per row.
            foreach ($missingCols as $mc) {
                $errors[] = "عمود مفقود في الملف: {$mc}";
            }

            $code = $get('question_code');
            $category = $this->resolveCategory($get('question_category'), $errors);
            $type = mb_strtolower($get('question_type'));
            if (! in_array($type, self::ALLOWED_TYPES, true)) {
                $errors[] = 'نوع سؤال غير مدعوم: '.($type ?: '(فارغ)');
                $type = $type ?: null;
            }

            $isFullImage = $this->boolish($get('is_full_image_question'));
            $body = $get('question_text');
            $questionImage = $get('question_image');

            // Image-extension / URL validation for the question image.
            if ($questionImage !== '') {
                $this->validateImageRef($questionImage, 'question_image', $errors);
            }

            // No text and no image.
            if ($body === '' && $questionImage === '') {
                $errors[] = 'سؤال بدون نص وبدون صورة.';
            }
            // Full-image question requires a code.
            if ($isFullImage && $code === '') {
                $errors[] = 'سؤال صورة كاملة بدون كود.';
            }

            // Classification lookups (by name, scope-aware).
            $subjectId = $this->resolveLookup($this->subjects, $get('subject'), 'مادة', $errors, optional: true);
            $gradeId = $this->resolveGrade($get('grade'), $errors);
            $semesterId = $this->resolveLookup($this->semesters, $get('semester'), 'فصل دراسي', $errors, optional: true);
            $weekId = $this->resolveLookup($this->weeks, $get('week'), 'أسبوع', $errors, optional: true);
            $skillId = $this->resolveLookup($this->skills, $get('skill'), 'مهارة', $errors, optional: true);
            $classId = $this->resolveLookup($this->classes, $get('class'), 'فصل', $errors, optional: true);

            $difficulty = $this->resolveDifficulty($get('difficulty_level'), $errors);
            $points = $this->resolveScore($get('score'));

            // Per-type answer resolution + validation.
            $answerPayload = $type ? $this->resolveAnswers($type, $cells, $colByHeader, $get, $errors) : [];

            // Duplicate code checks.
            $status = 'valid';
            if ($code !== '') {
                $lc = mb_strtolower($code);
                if (isset($seenInFile[$lc])) {
                    $errors[] = "كود مكرر داخل الملف: {$code}";
                    $status = 'duplicate';
                } elseif (in_array($lc, $existingCodes, true)) {
                    $errors[] = "كود مكرر داخل البنك: {$code}";
                    $status = 'duplicate';
                }
                $seenInFile[$lc] = true;
            }

            if (! empty($errors) && $status !== 'duplicate') {
                $status = 'invalid';
            }

            $contentType = $this->contentType($body, $questionImage, $isFullImage);

            $out[] = array_merge([
                'rowNumber' => $rowNum,
                'status' => $status,
                'errors' => $errors,
                'raw' => $raw,
                // payload
                'question_category' => $category,
                'question_code' => $code !== '' ? $code : null,
                'type' => $type,
                'body_ar' => $body !== '' ? $body : null,
                'explanation' => $get('explanation') !== '' ? $get('explanation') : null,
                'difficulty' => $difficulty,
                'points' => $points,
                'is_full_image_question' => $isFullImage,
                'question_content_type' => $contentType,
                'attachment_url' => $questionImage !== '' ? $questionImage : null,
                'subject_id' => $subjectId,
                'grade_id' => $gradeId,
                'class_id' => $classId,
                'semester_id' => $semesterId,
                'week_id' => $weekId,
                'skill_id' => $skillId,
            ], $answerPayload);
        }

        return $out;
    }

    // ── answer resolution ───────────────────────────────────────────────────

    /**
     * Translate the Excel answer columns into the keys MapAnswerData reads.
     */
    private function resolveAnswers(string $type, array $cells, array $colByHeader, callable $get, array &$errors): array
    {
        return match ($type) {
            'mcq' => $this->resolveMcq($cells, $colByHeader, $get, $errors),
            'true_false' => $this->resolveTrueFalse($get, $errors),
            'short' => ['short_answer' => $this->requireModelAnswer($get, $errors)],
            'essay' => ['essay_answer' => $this->requireModelAnswer($get, $errors)],
            'fill_blank' => $this->resolveFillBlank($get, $errors),
            'matching' => $this->resolveMatching($get, $errors),
            default => [],
        };
    }

    private function resolveMcq(array $cells, array $colByHeader, callable $get, array &$errors): array
    {
        $options = [];
        for ($i = 1; $i <= 5; $i++) {
            $text = isset($colByHeader["answer_{$i}_text"])
                ? trim((string) ($cells[$colByHeader["answer_{$i}_text"]] ?? ''))
                : '';
            $img = isset($colByHeader["answer_{$i}_image"])
                ? trim((string) ($cells[$colByHeader["answer_{$i}_image"]] ?? ''))
                : '';
            if ($img !== '') {
                $this->validateImageRef($img, "answer_{$i}_image", $errors);
            }
            if ($text !== '' || $img !== '') {
                $options[] = $text !== '' ? $text : $img;
            }
        }

        if (count($options) < 2) {
            $errors[] = 'سؤال اختيار بدون اختيارات كافية (خياران على الأقل).';
        }

        $correctIndex = $this->parseCorrectIndex($get('correct_answer'), count($options));
        if ($correctIndex === null) {
            $errors[] = 'إجابة صحيحة غير موجودة أو خارج نطاق الخيارات.';
        }

        return ['options_ar' => $options, 'correct_index' => $correctIndex];
    }

    private function resolveTrueFalse(callable $get, array &$errors): array
    {
        $raw = mb_strtolower($get('correct_answer'));
        $true = in_array($raw, ['true', '1', 'صح', 'نعم', 'yes'], true);
        $false = in_array($raw, ['false', '0', 'خطأ', 'لا', 'no'], true);
        if (! $true && ! $false) {
            $errors[] = 'سؤال صح وخطأ بإجابة غير صحيحة (true/false).';

            return ['correct' => null];
        }

        return ['correct' => $true ? 'true' : 'false'];
    }

    private function requireModelAnswer(callable $get, array &$errors): ?string
    {
        $a = $get('correct_answer');
        if ($a === '') {
            $errors[] = 'إجابة صحيحة (نموذجية) غير موجودة.';

            return null;
        }

        return $a;
    }

    private function resolveFillBlank(callable $get, array &$errors): array
    {
        $blanks = array_values(array_filter(array_map('trim', explode('|', $get('correct_answer'))), fn ($v) => $v !== ''));
        if (count($blanks) < 1) {
            $errors[] = 'سؤال املأ الفراغ بدون إجابات.';
        }

        return ['blanks' => $blanks];
    }

    private function resolveMatching(callable $get, array &$errors): array
    {
        $left = [];
        $right = [];
        foreach (explode('|', $get('correct_answer')) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || ! str_contains($pair, '=>')) {
                continue;
            }
            [$r, $l] = array_map('trim', explode('=>', $pair, 2));
            if ($l !== '' && $r !== '') {
                $left[] = $l;
                $right[] = $r;
            }
        }
        if (count($left) < 2) {
            $errors[] = 'سؤال توصيل بأقل من زوجين صحيحين (يمين=>يسار).';
        }

        return ['matching_left' => $left, 'matching_right' => $right];
    }

    private function parseCorrectIndex(string $value, int $optionCount): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        // Letter A..E
        $upper = strtoupper($value);
        $letters = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4];
        if (isset($letters[$upper])) {
            $idx = $letters[$upper];
        } elseif (is_numeric($value)) {
            $idx = ((int) $value) - 1; // 1-based in the sheet
        } else {
            return null;
        }

        return ($idx >= 0 && $idx < $optionCount) ? $idx : null;
    }

    // ── lookups ─────────────────────────────────────────────────────────────

    private function loadLookups(QuestionBank $bank, ?int $schoolId): void
    {
        $effSchool = $schoolId ?? $bank->school_id;

        $this->subjects = $this->keyByName(
            Subject::query()
                ->where('is_active', true)
                ->when($effSchool, fn ($q) => $q->where(fn ($w) => $w->where('school_id', $effSchool)->orWhereNull('school_id')))
                ->get(['id', 'name', 'name_en', 'short_name_ar', 'short_name_en']),
            ['name', 'name_en', 'short_name_ar', 'short_name_en']
        );

        $this->skills = $this->keyByName(
            Skill::query()
                ->when($effSchool, fn ($q) => $q->where(fn ($w) => $w->where('school_id', $effSchool)->orWhereNull('school_id')))
                ->get(['id', 'name']),
            ['name']
        );

        $this->semesters = $this->keyByName(
            AcademicTerm::query()
                ->when($effSchool, fn ($q) => $q->whereHas('academicYear', fn ($a) => $a->where('school_id', $effSchool)))
                ->get(['id', 'name']),
            ['name']
        );

        $weekQuery = StudyWeek::query();
        if ($effSchool) {
            // study_weeks.academic_term_id → academic_terms → academic_years.school_id
            $termIds = AcademicTerm::query()
                ->whereHas('academicYear', fn ($a) => $a->where('school_id', $effSchool))
                ->pluck('id');
            $weekQuery->whereIn('academic_term_id', $termIds);
        }
        $this->weeks = $this->keyByName($weekQuery->get(['id', 'name']), ['name']);

        $classQuery = ClassRoom::query()->where('is_active', true);
        if ($effSchool) {
            $classQuery->whereHas('section', fn ($q) => $q->where('school_id', $effSchool));
        }
        $classes = $classQuery->get(['id', 'name', 'grade_level']);
        $this->classes = $this->keyByName($classes, ['name']);
        $this->gradeLevels = $classes->pluck('grade_level')->map(fn ($g) => (string) $g)->unique()->all();
    }

    private function keyByName($collection, array $fields): array
    {
        $map = [];
        foreach ($collection as $item) {
            foreach ($fields as $f) {
                $val = $this->norm($item->{$f} ?? '');
                if ($val !== '') {
                    $map[$val] = $item->id;
                }
            }
        }

        return $map;
    }

    private function resolveLookup(array $map, string $value, string $label, array &$errors, bool $optional = false): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $key = $this->norm($value);
        if (isset($map[$key])) {
            return $map[$key];
        }
        $errors[] = "{$label} غير موجودة: {$value}";

        return null;
    }

    private function resolveGrade(string $value, array &$errors): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            $errors[] = "صف غير صحيح: {$value}";

            return null;
        }
        // grade_id here is the grade_level integer (matches manual create form).
        if (! empty($this->gradeLevels) && ! in_array($value, $this->gradeLevels, true)) {
            $errors[] = "صف غير موجود: {$value}";

            return null;
        }

        return (int) $value;
    }

    private function resolveDifficulty(string $value, array &$errors): int
    {
        $value = $this->norm($value);
        if ($value === '') {
            return 1;
        }
        if (! isset(self::DIFFICULTY_MAP[$value])) {
            $errors[] = "مستوى صعوبة غير صحيح: {$value}";

            return 1;
        }

        return self::DIFFICULTY_MAP[$value];
    }

    private function resolveScore(string $value): float
    {
        $value = trim($value);

        return ($value !== '' && is_numeric($value)) ? (float) $value : 1.0;
    }

    private function resolveCategory(string $value, array &$errors): string
    {
        $value = $this->norm($value);
        if ($value === '') {
            return 'normal';
        }
        if (! in_array($value, self::ALLOWED_CATEGORIES, true)) {
            $errors[] = "تصنيف غير مدعوم: {$value}";

            return 'normal';
        }

        return $value;
    }

    private function validateImageRef(string $ref, string $field, array &$errors): void
    {
        // Method 1 — a URL. Method 2 (ZIP filename) not consumed here.
        $isUrl = (bool) filter_var($ref, FILTER_VALIDATE_URL);
        $path = $isUrl ? parse_url($ref, PHP_URL_PATH) : $ref;
        $ext = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        if (! $isUrl && ! str_contains($ref, '.')) {
            $errors[] = "رابط صورة غير صالح ({$field}): {$ref}";

            return;
        }
        if ($ext !== '' && ! in_array($ext, self::ALLOWED_IMAGE_EXT, true)) {
            $errors[] = "امتداد صورة غير مدعوم ({$field}): .{$ext}";
        }
    }

    private function contentType(string $body, string $image, bool $isFullImage): string
    {
        if ($isFullImage) {
            return 'image';
        }
        if ($body !== '' && $image !== '') {
            return 'mixed';
        }
        if ($image !== '') {
            return 'image';
        }

        return 'text';
    }

    private function boolish(string $value): bool
    {
        return in_array($this->norm($value), ['1', 'yes', 'true', 'نعم', 'صح'], true);
    }

    private function rowHasData(array $cells): bool
    {
        foreach ($cells as $v) {
            if (trim((string) $v) !== '') {
                return true;
            }
        }

        return false;
    }

    private function norm($value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
