<?php

namespace App\Modules\Subjects\Actions;

use App\Models\Subject;
use App\Modules\Subjects\DTOs\SubjectImportResult;
use App\Modules\Subjects\Repositories\Contracts\SubjectRepository;
use Illuminate\Http\UploadedFile;

/**
 * Bulk-creates subjects from an uploaded Excel/CSV file that follows the
 * platform template (one header row, one subject per row).
 *
 * Header mapping is keyword-based so column order does not matter. Rows that
 * are missing the required Arabic name are skipped and reported.
 */
final class ImportSubjectsFromExcelAction
{
    public function __construct(private SubjectRepository $subjects) {}

    public function execute(UploadedFile $file, ?int $schoolId): SubjectImportResult
    {
        $matrix = $this->readMatrix($file);

        if (count($matrix) === 0) {
            return new SubjectImportResult();
        }

        $header = array_map(fn ($v) => $this->normalizeHeader((string) ($v ?? '')), array_shift($matrix));

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $total = 0;

        foreach ($matrix as $i => $row) {
            $rowNumber = $i + 2; // +1 for shifted header, +1 to be 1-based
            if ($this->isEmptyRow($row)) {
                continue;
            }
            $total++;

            $get = fn (array $keywords) => $this->cell($header, $row, $keywords);

            $name = $get(['الاسم بالعربي', 'العنوان بالعربي', 'اسم المادة', 'الاسم', 'name ar', 'name']);
            if ($name === null || $name === '') {
                $failed++;
                $errors[] = ['row' => $rowNumber, 'reason' => __('sprint4.subjects.import.error_no_name')];
                continue;
            }

            // Idempotency: skip if a subject with the same name already exists in this school.
            $exists = Subject::query()
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->where('name', $name)
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            try {
                $this->subjects->create([
                    'school_id' => $schoolId,
                    'name' => $name,
                    'name_en' => $get(['الاسم بالإنجليزي', 'العنوان بالإنجليزي', 'name en', 'english']),
                    'short_name_ar' => $get(['المختصر بالعربي', 'الاسم المختصر بالعربي', 'short ar', 'مختصر عربي']),
                    'short_name_en' => $get(['المختصر بالإنجليزي', 'الاسم المختصر بالإنجليزي', 'short en', 'مختصر انجليزي']),
                    'language' => $this->normalizeLanguage($get(['لغة المادة', 'اللغة', 'language'])),
                    'code' => $get(['الكود', 'code']),
                    'section' => $get(['الشعبة', 'section', 'المسار']),
                    'grade_levels' => $this->parseGrades($get(['الصف', 'الصفوف', 'grade', 'الصف الدراسي'])),
                    'certificate_order' => $this->parseInt($get(['الترتيب', 'ترتيب', 'order'])),
                    'credit_hours' => $this->parseInt($get(['عدد الحصص', 'الحصص', 'weekly', 'حصص'])),
                    'total_hours' => $this->parseInt($get(['عدد الساعات', 'الساعات', 'hours'])),
                    'credit_value' => $this->parseInt($get(['القيمة المعتمدة', 'القيمة', 'credit'])),
                    'is_active' => true,
                    'source' => 'excel',
                ]);
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $rowNumber, 'reason' => $e->getMessage()];
            }
        }

        return new SubjectImportResult(
            total: $total,
            created: $created,
            skipped: $skipped,
            failed: $failed,
            errors: $errors,
        );
    }

    /** @return array<int, array<int, mixed>> */
    private function readMatrix(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->getRealPath();

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->readCsv($path);
        }

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                return $this->readXlsx($path);
            }
            throw new \RuntimeException(__('sprint4.subjects.import.error_missing_library'));
        }

        throw new \InvalidArgumentException(__('sprint4.subjects.import.error_invalid_file'));
    }

    /** @return array<int, array<int, mixed>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException(__('sprint4.subjects.import.error_parse_failed'));
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /** @return array<int, array<int, mixed>> */
    private function readXlsx(string $path): array
    {
        $ioFactory = '\\PhpOffice\\PhpSpreadsheet\\IOFactory';
        $reader = $ioFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    /** @param array<int, string> $header @param array<int, mixed> $row @param array<int, string> $keywords */
    private function cell(array $header, array $row, array $keywords): ?string
    {
        foreach ($header as $colIdx => $h) {
            foreach ($keywords as $k) {
                if ($h !== '' && mb_strpos($h, $this->normalizeHeader($k)) !== false) {
                    $v = $row[$colIdx] ?? null;
                    if ($v === null || trim((string) $v) === '') {
                        return null;
                    }
                    return trim((string) $v);
                }
            }
        }
        return null;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $value) ?? $value;
        $value = preg_replace('/[\.\-:_\s]+/u', ' ', $value) ?? $value;
        return mb_strtolower(trim($value));
    }

    /** @param array<int, mixed> $row */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function parseInt(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        return (int) preg_replace('/[^0-9]/', '', $value);
    }

    /** @return array<int, int>|null */
    private function parseGrades(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        preg_match_all('/\d+/', $value, $m);
        $levels = array_values(array_filter(array_map('intval', $m[0] ?? []), fn ($g) => $g >= 1 && $g <= 12));

        return count($levels) > 0 ? array_values(array_unique($levels)) : null;
    }

    private function normalizeLanguage(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = mb_strtolower(trim($value));
        if (str_contains($v, 'en') || str_contains($v, 'إنجليز') || str_contains($v, 'انجليز')) {
            return 'en';
        }
        if (str_contains($v, 'ar') || str_contains($v, 'عرب')) {
            return 'ar';
        }
        return null;
    }
}
