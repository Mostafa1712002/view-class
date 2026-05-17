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
        $get = function (array $keywords) use ($header, $row): ?string {
            foreach ($header as $colIdx => $h) {
                foreach ($keywords as $k) {
                    if ($h !== '' && mb_strpos($h, $k) !== false) {
                        $v = $row[$colIdx] ?? null;
                        if ($v === null || trim((string) $v) === '') return null;
                        return trim((string) $v);
                    }
                }
            }
            return null;
        };

        return new NoorRowDto(
            rowNumber: $rowNumber,
            nationalId: $get(['الهوية', 'هوية', 'السجل', 'national']),
            academicNumber: $get(['الرقم الأكاديمي', 'اكاديمي', 'أكاديمي', 'academic']),
            name: $get(['الاسم', 'اسم الطالب', 'اسم المعلم', 'name']),
            gender: $get(['الجنس', 'gender']),
            birthDate: $get(['تاريخ الميلاد', 'الميلاد', 'birth']),
            phone: $get(['الجوال', 'جوال', 'هاتف', 'phone', 'mobile']),
            email: $get(['البريد', 'الإيميل', 'الايميل', 'email']),
            classRoom: $get(['الصف', 'الفصل', 'class']),
            specialization: $get(['التخصص', 'specialization']),
            raw: array_combine($header, array_map(fn ($v) => is_string($v) ? $v : (string) ($v ?? '')
                , array_slice($row, 0, count($header))
            )) ?: [],
        );
    }
}
