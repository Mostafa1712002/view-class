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
 * Real Noor exports are NOT a plain header+rows sheet: they carry several
 * metadata rows at the top (the grade «الصف» and department «القسم» of the
 * class, the school name, the academic year…), and only then the student
 * table whose header looks like «م | رقم الطالب | اسم الطالب | الفصل».
 *
 * So we don't assume row 1 is the header: we locate the student-table header
 * (the first row carrying both a name column and an id/serial column), lift the
 * grade/department out of the metadata rows above it, and apply that grade to
 * every student row (Noor stores it once per sheet, not per row). Header mapping
 * stays keyword-based so column order does not matter, and a plain
 * header-in-row-1 file still parses (the locator falls back to row 0).
 */
final class ParseNoorExcel
{
    /** @return array<int, NoorRowDto> */
    public function execute(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->getRealPath();

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseMatrix($this->csvMatrix($path));
        }

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                return $this->parseMatrix($this->xlsxMatrix($path));
            }
            throw new \RuntimeException(__('noor.errors.missing_library'));
        }

        throw new \InvalidArgumentException(__('noor.errors.invalid_file'));
    }

    /** @return array<int, array<int, mixed>> */
    private function csvMatrix(string $path): array
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

        $matrix = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $matrix[] = $row;
        }
        fclose($handle);

        return $matrix;
    }

    /** @return array<int, array<int, mixed>> */
    private function xlsxMatrix(string $path): array
    {
        $ioFactory = '\\PhpOffice\\PhpSpreadsheet\\IOFactory';
        $reader = $ioFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    /**
     * Turn a raw cell matrix into DTOs: find the student-table header, read the
     * sheet-level metadata above it, then map each subsequent non-empty row.
     *
     * @param  array<int, array<int, mixed>> $matrix
     * @return array<int, NoorRowDto>
     */
    private function parseMatrix(array $matrix): array
    {
        if ($matrix === []) {
            return [];
        }

        $headerIdx = $this->locateHeaderRow($matrix);
        $meta      = $this->extractMeta(array_slice($matrix, 0, $headerIdx));
        $header    = array_map(fn ($v) => $this->normalizeHeader((string) ($v ?? '')), $matrix[$headerIdx]);

        $rows = [];
        foreach ($matrix as $i => $row) {
            if ($i <= $headerIdx) {
                continue;
            }
            if ($this->isEmptyRow($row)) {
                continue;
            }
            $rows[] = $this->buildDto($i + 1, $header, $row, $meta);
        }

        return $rows;
    }

    /**
     * The student-table header is the first row that carries both a name column
     * and an id/serial column. Falls back to row 0 (plain header) when no such
     * row is found, preserving behaviour for simple files.
     *
     * @param array<int, array<int, mixed>> $matrix
     */
    private function locateHeaderRow(array $matrix): int
    {
        foreach ($matrix as $i => $row) {
            $cells = array_map(fn ($v) => $this->normalizeHeader((string) ($v ?? '')), $row);
            $hasName = false;
            $hasId   = false;
            foreach ($cells as $c) {
                if ($c === '') {
                    continue;
                }
                if (mb_strpos($c, 'اسم الطالب') !== false || $c === 'الاسم' || mb_strpos($c, 'الاسم') !== false || $c === 'اسم') {
                    $hasName = true;
                }
                if (mb_strpos($c, 'رقم الطالب') !== false || mb_strpos($c, 'هوية') !== false
                    || mb_strpos($c, 'رقم الهوية') !== false || mb_strpos($c, 'السجل') !== false || $c === 'م') {
                    $hasId = true;
                }
            }
            if ($hasName && $hasId) {
                return $i;
            }
        }

        return 0;
    }

    /**
     * Lift the grade «الصف» and department «القسم» from the metadata rows that
     * sit above the student table (Noor lays them out as «label : value» spread
     * across columns, so we take the longest non-label cell in the same row).
     *
     * @param  array<int, array<int, mixed>> $rowsAbove
     * @return array{grade:?string, section:?string}
     */
    private function extractMeta(array $rowsAbove): array
    {
        $grade = null;
        $section = null;

        foreach ($rowsAbove as $row) {
            $cells = [];
            foreach ($row as $c) {
                $t = trim((string) ($c ?? ''));
                if ($t !== '' && $t !== ':') {
                    $cells[] = $t;
                }
            }
            if (count($cells) < 2) {
                continue;
            }

            foreach ($cells as $cell) {
                $n = $this->normalizeHeader($cell);
                if ($grade === null && (mb_strpos($n, 'الصف') !== false || mb_strpos($n, 'المرحلة') !== false)) {
                    $grade = $this->otherCell($cells, $cell);
                }
                if ($section === null && (mb_strpos($n, 'القسم') !== false || mb_strpos($n, 'المسار') !== false)) {
                    $section = $this->otherCell($cells, $cell);
                }
            }
        }

        return ['grade' => $grade, 'section' => $section];
    }

    /** The longest cell in the row that is not the label itself. */
    private function otherCell(array $cells, string $label): ?string
    {
        $best = null;
        foreach ($cells as $c) {
            if ($c === $label) {
                continue;
            }
            if ($best === null || mb_strlen($c) > mb_strlen($best)) {
                $best = $c;
            }
        }

        return $best;
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
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<int,string>      $header
     * @param array<int,mixed>       $row
     * @param array{grade:?string,section:?string} $meta
     */
    private function buildDto(int $rowNumber, array $header, array $row, array $meta = ['grade' => null, 'section' => null]): NoorRowDto
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
            // Noor's «رقم الطالب» column carries the national/record id.
            nationalId: $get(['هوية الطالب', 'رقم هوية الطالب', 'رقم الطالب', 'الهوية', 'رقم الهوية', 'هوية', 'السجل المدني', 'السجل', 'national'], ['ولي', 'guardian', 'parent']),
            academicNumber: $get(['الرقم الأكاديمي', 'اكاديمي', 'أكاديمي', 'academic']),
            name: $get(['اسم الطالب', 'الاسم', 'اسم المعلم', 'name'], ['ولي', 'guardian', 'parent']),
            gender: $get(['الجنس', 'النوع', 'gender']),
            birthDate: $get(['تاريخ الميلاد', 'الميلاد', 'birth']),
            phone: $get(['جوال الطالب', 'الجوال', 'جوال', 'هاتف', 'phone', 'mobile'], ['ولي', 'guardian', 'parent']),
            email: $get(['البريد', 'الإيميل', 'الايميل', 'email']),
            // Grade/department are sheet-level in Noor; fall back to the metadata.
            grade: $get(['الصف', 'المرحلة', 'grade']) ?? $meta['grade'],
            classRoom: $get(['الفصل', 'الشعبة', 'class', 'section']),
            specialization: $get(['التخصص', 'specialization']) ?? $meta['section'],
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
