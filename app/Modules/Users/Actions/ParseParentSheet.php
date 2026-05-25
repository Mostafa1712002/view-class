<?php

namespace App\Modules\Users\Actions;

use Illuminate\Http\UploadedFile;

/**
 * Parses an uploaded parents sheet (CSV/TXT natively, XLSX/XLS via PhpSpreadsheet)
 * into an array of associative rows keyed by Arabic-aware header keywords.
 *
 * Header mapping is keyword-based so column order does not matter.
 */
final class ParseParentSheet
{
    /** @return array<int, array<string,?string>> each row: ['_row'=>n, 'national_id'=>..., ...] */
    public function execute(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->getRealPath();

        if (in_array($ext, ['csv', 'txt'], true)) {
            $matrix = $this->readCsv($path);
        } elseif (in_array($ext, ['xlsx', 'xls'], true) && class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $matrix = $this->readXlsx($path);
        } else {
            throw new \InvalidArgumentException(__('users.import_no_file'));
        }

        $rows = [];
        $header = null;
        foreach ($matrix as $i => $raw) {
            if ($this->isEmptyRow($raw)) {
                continue;
            }
            if ($header === null) {
                $header = array_map(fn ($v) => $this->normalizeHeader((string) $v), $raw);
                continue;
            }
            $rows[] = $this->mapRow($i + 1, $header, $raw);
        }

        return $rows;
    }

    /** @return array<int, array<int,?string>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException(__('users.import_no_file'));
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

    /** @return array<int, array<int,?string>> */
    private function readXlsx(string $path): array
    {
        $ioFactory = '\\PhpOffice\\PhpSpreadsheet\\IOFactory';
        $reader = $ioFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    private function mapRow(int $rowNumber, array $header, array $row): array
    {
        $get = function (array $keywords) use ($header, $row): ?string {
            foreach ($header as $colIdx => $h) {
                foreach ($keywords as $k) {
                    if ($h !== '' && mb_strpos($h, $k) !== false) {
                        $v = $row[$colIdx] ?? null;

                        return ($v === null || trim((string) $v) === '') ? null : trim((string) $v);
                    }
                }
            }

            return null;
        };

        return [
            '_row' => $rowNumber,
            'national_id' => $get(['الهوية', 'هوية', 'national']),
            'name' => $get(['الاسم الكامل', 'الاسم', 'name']),
            'first_name' => $get(['الاسم الاول', 'الاسم الأول', 'first']),
            'father_name' => $get(['اسم الاب', 'اسم الأب', 'father']),
            'grandfather_name' => $get(['اسم الجد', 'grandfather']),
            'family_name' => $get(['اسم العائله', 'اسم العائلة', 'العائلة', 'family']),
            'name_en' => $get(['بالانجليزي', 'بالإنجليزي', 'english', 'name en']),
            'username' => $get(['اسم المستخدم', 'username']),
            'email' => $get(['البريد', 'الايميل', 'الإيميل', 'email']),
            'phone' => $get(['رقم الهاتف', 'الهاتف', 'phone']),
            'phone_secondary' => $get(['رقم الجوال', 'الجوال', 'جوال', 'mobile']),
            'whatsapp' => $get(['الواتساب', 'واتساب', 'whatsapp']),
            'gender' => $get(['الجنس', 'gender']),
            'date_of_birth' => $get(['تاريخ الميلاد', 'الميلاد', 'birth']),
            'birth_place' => $get(['مكان الولاده', 'مكان الولادة', 'birth place']),
            'address' => $get(['العنوان', 'address']),
            'nationality' => $get(['الجنسيه', 'الجنسية', 'nationality']),
            'student_national_id' => $get(['هوية الطالب', 'رقم الطالب', 'student id', 'student national']),
        ];
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
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
}
