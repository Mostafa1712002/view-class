<?php

namespace App\Modules\StudentImport\Actions;

use App\Modules\StudentImport\DTOs\StudentImportRowDto;
use Illuminate\Http\UploadedFile;

/**
 * Parses an uploaded `students_import.xlsx` (or csv) into StudentImportRowDto[].
 *
 * Because this is the platform's OWN template with fixed English headers, the
 * mapping is exact (normalized header → internal key). A missing required
 * header means the operator edited the template → hard "invalid format" error.
 *
 *   - CSV / TXT  → native fgetcsv
 *   - XLSX / XLS → PhpSpreadsheet (already a dependency, used by NoorImport)
 */
final class ParseStudentExcel
{
    /** normalized header => DTO key */
    private const HEADER_MAP = [
        'identity number' => 'nationalId',
        'acceptance year' => 'acceptanceYear',
        'first name' => 'firstName',
        'last name' => 'lastName',
        'father name' => 'fatherName',
        'grand father name' => 'grandfatherName',
        'username' => 'username',
        'password' => 'password',
        'grade' => 'grade',
        'class' => 'classRoom',
        'gender' => 'gender',
        'mobile number' => 'mobile',
        'email' => 'email',
        'birthdate' => 'birthDate',
        'birth place' => 'birthPlace',
        'nationality' => 'nationality',
        'passport id' => 'passportId',
        'academic id' => 'academicId',
        'previous school' => 'previousSchool',
        'fingerprint id' => 'fingerprintId',
        'father identity number' => 'fatherNationalId',
        'father mobile number' => 'fatherMobile',
        'mother identity number' => 'motherNationalId',
        'mother full name' => 'motherFullName',
        'mother mobile number' => 'motherMobile',
        'first name (english)' => 'firstNameEn',
        'father name (english)' => 'fatherNameEn',
        'grand father name (english)' => 'grandfatherNameEn',
        'last name (english)' => 'lastNameEn',
        'sit number' => 'seatNumber',
    ];

    /** @return array<int, StudentImportRowDto> */
    public function execute(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->getRealPath();

        if (in_array($ext, ['csv', 'txt'], true)) {
            $matrix = $this->readCsv($path);
        } elseif (in_array($ext, ['xlsx', 'xls'], true)) {
            $matrix = $this->readXlsx($path);
        } else {
            throw new \InvalidArgumentException(__('student_import.errors.invalid_file'));
        }

        if (empty($matrix)) {
            return [];
        }

        $header = array_map(fn ($v) => $this->normalizeHeader((string) ($v ?? '')), array_shift($matrix));
        $colIndex = $this->buildColumnIndex($header);

        // Required columns must be present in the header row.
        foreach (['nationalId', 'firstName', 'lastName', 'fatherName'] as $req) {
            if (! array_key_exists($req, $colIndex)) {
                throw new \RuntimeException(__('student_import.errors.bad_format'));
            }
        }

        $rows = [];
        $rowNumber = 1; // header was row 1
        foreach ($matrix as $raw) {
            $rowNumber++;
            if ($this->isEmptyRow($raw)) {
                continue;
            }
            $rows[] = $this->buildDto($rowNumber, $colIndex, $raw);
        }

        return $rows;
    }

    /** @return array<int, array<int, mixed>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException(__('student_import.errors.parse_failed'));
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

    /** @return array<int, array<int, mixed>> */
    private function readXlsx(string $path): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException(__('student_import.errors.missing_library'));
        }
        $ioFactory = '\\PhpOffice\\PhpSpreadsheet\\IOFactory';
        $reader = $ioFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
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

    private function buildDto(int $rowNumber, array $colIndex, array $raw): StudentImportRowDto
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

        return new StudentImportRowDto(
            rowNumber: $rowNumber,
            nationalId: $get('nationalId'),
            acceptanceYear: $get('acceptanceYear'),
            firstName: $get('firstName'),
            lastName: $get('lastName'),
            fatherName: $get('fatherName'),
            grandfatherName: $get('grandfatherName'),
            username: $get('username'),
            password: $get('password'),
            grade: $get('grade'),
            classRoom: $get('classRoom'),
            gender: $get('gender'),
            mobile: $get('mobile'),
            email: $get('email'),
            birthDate: $get('birthDate'),
            birthPlace: $get('birthPlace'),
            nationality: $get('nationality'),
            passportId: $get('passportId'),
            academicId: $get('academicId'),
            previousSchool: $get('previousSchool'),
            fingerprintId: $get('fingerprintId'),
            fatherNationalId: $get('fatherNationalId'),
            fatherMobile: $get('fatherMobile'),
            motherNationalId: $get('motherNationalId'),
            motherFullName: $get('motherFullName'),
            motherMobile: $get('motherMobile'),
            firstNameEn: $get('firstNameEn'),
            fatherNameEn: $get('fatherNameEn'),
            grandfatherNameEn: $get('grandfatherNameEn'),
            lastNameEn: $get('lastNameEn'),
            seatNumber: $get('seatNumber'),
        );
    }

    private function normalizeHeader(string $value): string
    {
        $value = str_replace('*', '', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

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
