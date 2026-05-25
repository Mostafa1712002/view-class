<?php

namespace App\Modules\NoorImport\Actions;

use App\Modules\NoorImport\DTOs\NoorRowDto;
use Illuminate\Http\UploadedFile;

/**
 * Parses an uploaded Noor file into a list of NoorRowDto.
 *
 * Strategy:
 *   - CSV / TXT  → native PHP fgetcsv  (works today, no composer deps)
 *   - XLSX / XLS → PhpSpreadsheet if the class exists; else throw
 *
 * Header mapping is by Arabic header keywords so column order does not matter.
 */
final class ParseNoorExcel
{
    /** @return array<int, NoorRowDto> */
    public function execute(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->getRealPath();

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseCsv($path);
        }

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                return $this->parseXlsxViaPhpSpreadsheet($path);
            }
            throw new \RuntimeException(__('noor.errors.missing_library'));
        }

        throw new \InvalidArgumentException(__('noor.errors.invalid_file'));
    }

    /** @return array<int, NoorRowDto> */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException(__('noor.errors.parse_failed'));
        }

        // Strip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = [];
        $header = null;
        $rowNumber = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rowNumber++;
            if ($header === null) {
                $header = array_map(fn ($v) => $this->normalizeHeader((string) $v), $row);
                continue;
            }
            if ($this->isEmptyRow($row)) {
                continue;
            }
            $rows[] = $this->buildDto($rowNumber, $header, $row);
        }
        fclose($handle);
        return $rows;
    }

    /** @return array<int, NoorRowDto> */
    private function parseXlsxViaPhpSpreadsheet(string $path): array
    {
        // Use string-based class names so static analysis stays clean
        // even when the package is not installed.
        $ioFactory = '\\PhpOffice\\PhpSpreadsheet\\IOFactory';
        $reader = $ioFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray(null, true, true, false);

        $rows = [];
        $header = null;
        foreach ($matrix as $i => $row) {
            $rowNumber = $i + 1;
            if ($header === null) {
                $header = array_map(fn ($v) => $this->normalizeHeader((string) $v), $row);
                continue;
            }
            if ($this->isEmptyRow($row)) {
                continue;
            }
            $rows[] = $this->buildDto($rowNumber, $header, $row);
        }
        return $rows;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        // strip Arabic diacritics & common punctuation, lowercase ASCII
        $value = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $value) ?? $value;
        $value = preg_replace('/[\.\-:_\s]+/u', ' ', $value) ?? $value;
        return mb_strtolower(trim($value));
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') return false;
        }
        return true;
    }

    private function buildDto(int $rowNumber, array $header, array $row): NoorRowDto
    {
        // $exclude lets student-only fields ignore columns that belong to the
        // parent (e.g. "هوية ولي الأمر" must not be picked up as the student id).
        $get = function (array $keywords, array $exclude = []) use ($header, $row): ?string {
            foreach ($header as $colIdx => $h) {
                if ($h === '') continue;
                foreach ($exclude as $x) {
                    if (mb_strpos($h, $x) !== false) continue 2;
                }
                foreach ($keywords as $k) {
                    if (mb_strpos($h, $k) !== false) {
                        $v = $row[$colIdx] ?? null;
                        if ($v === null || trim((string) $v) === '') {
                            // Keep scanning other columns instead of giving up.
                            continue 2;
                        }
                        return trim((string) $v);
                    }
                }
            }
            return null;
        };

        return new NoorRowDto(
            rowNumber: $rowNumber,
            // Student identity: prefer the explicit student/national-id headers,
            // never the parent (ولي الأمر) one which we capture separately below.
            nationalId: $get(['هوية الطالب', 'رقم هوية الطالب', 'الهوية', 'رقم الهوية', 'هوية', 'السجل المدني', 'السجل', 'national'], ['ولي', 'guardian', 'parent']),
            academicNumber: $get(['الرقم الأكاديمي', 'اكاديمي', 'أكاديمي', 'academic']),
            name: $get(['اسم الطالب', 'الاسم', 'اسم المعلم', 'name'], ['ولي', 'guardian', 'parent']),
            gender: $get(['الجنس', 'النوع', 'gender']),
            birthDate: $get(['تاريخ الميلاد', 'الميلاد', 'birth']),
            phone: $get(['جوال الطالب', 'الجوال', 'جوال', 'هاتف', 'phone', 'mobile'], ['ولي', 'guardian', 'parent']),
            email: $get(['البريد', 'الإيميل', 'الايميل', 'email']),
            grade: $get(['الصف', 'المرحلة', 'grade']),
            classRoom: $get(['الفصل', 'الشعبة', 'class', 'section']),
            specialization: $get(['التخصص', 'specialization']),
            nationality: $get(['الجنسية', 'nationality']),
            studentStatus: $get(['حالة الطالب', 'الحالة', 'status']),
            parentName: $get(['اسم ولي الامر', 'اسم ولي الأمر', 'ولي الامر', 'ولي الأمر', 'guardian name', 'parent name']),
            parentNationalId: $get(['هوية ولي الامر', 'هوية ولي الأمر', 'رقم هوية ولي الامر', 'رقم هوية ولي الأمر', 'سجل ولي الامر', 'guardian id', 'parent id']),
            parentPhone: $get(['جوال ولي الامر', 'جوال ولي الأمر', 'هاتف ولي الامر', 'هاتف ولي الأمر', 'guardian phone', 'parent phone']),
            raw: array_combine($header, array_map(fn ($v) => is_string($v) ? $v : (string) ($v ?? '')
                , array_slice($row, 0, count($header))
            )) ?: [],
        );
    }
}
