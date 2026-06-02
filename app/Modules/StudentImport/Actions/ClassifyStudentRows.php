<?php

namespace App\Modules\StudentImport\Actions;

use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\User;
use App\Modules\StudentImport\DTOs\StudentImportRowDto;

/**
 * Builds the preview rows for the "معاينة قبل الحفظ" screen.
 *
 * Each row is classified as:
 *   - invalid    : missing required field, unknown grade/class, or username clash
 *   - duplicate  : the same identity number appears more than once in THIS file
 *   - update     : a student with this identity number already exists in the school
 *   - new        : will be created on execute
 *
 * Divergences from the Noor importer (deliberate, per card #108):
 *   - Grade/Class are matched by STRICT existence (not fuzzy LIKE). If a Grade
 *     or Class is supplied but not found in the school, the row is invalid.
 *   - An explicit Username that collides with another user makes the row
 *     invalid (no silent auto-rename).
 *
 * The resolved section_id / class_id are stamped onto each output row so the
 * importer reuses them verbatim without re-resolving.
 */
final class ClassifyStudentRows
{
    /**
     * @param  array<int, StudentImportRowDto>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function execute(array $rows, int $schoolId): array
    {
        // Existing students in this school, by national id.
        $ids = array_values(array_filter(array_map(
            fn (StudentImportRowDto $r) => $r->nationalId,
            $rows
        )));

        $existing = [];
        if ($ids) {
            $existing = User::withTrashed()
                ->where('school_id', $schoolId)
                ->whereIn('national_id', $ids)
                ->pluck('id', 'national_id')
                ->mapWithKeys(fn ($id, $nid) => [(string) $nid => (int) $id])
                ->all();
        }

        // Section (grade) lookup: normalized name => id, for this school.
        $sections = Section::where('school_id', $schoolId)
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($s) => [$this->norm($s->name) => (int) $s->id])
            ->all();

        // Class lookup: "sectionId|normalizedName" and "sectionId|normalizedDivision" => id.
        $classMap = [];
        if ($sections) {
            ClassRoom::whereIn('section_id', array_values($sections))
                ->get(['id', 'section_id', 'name', 'division'])
                ->each(function ($c) use (&$classMap) {
                    $classMap[$c->section_id.'|'.$this->norm($c->name)] = (int) $c->id;
                    if ($c->division) {
                        $classMap[$c->section_id.'|'.$this->norm($c->division)] = (int) $c->id;
                    }
                });
        }

        $seen = [];
        $out = [];

        foreach ($rows as $row) {
            $data = $row->toArray();
            $data['name'] = $row->fullName();
            $data['resolvedSectionId'] = null;
            $data['resolvedClassId'] = null;

            $nid = $row->nationalId !== null ? trim((string) $row->nationalId) : '';

            // 1. Required fields (template's starred columns).
            $missing = $this->missingRequired($row);
            if ($missing !== null) {
                $out[] = $this->invalid($data, __('student_import.errors.missing_field', ['field' => $missing]));

                continue;
            }

            // 2. Duplicate within the file.
            if (isset($seen[$nid])) {
                $out[] = $this->invalid($data, __('student_import.preview.reason_duplicate'), 'duplicate');

                continue;
            }
            $seen[$nid] = true;

            $existingId = $existing[$nid] ?? null;

            // 3. Grade / Class strict existence (only when supplied).
            if ($row->grade !== null) {
                $sectionId = $sections[$this->norm($row->grade)] ?? null;
                if (! $sectionId) {
                    $out[] = $this->invalid($data, __('student_import.errors.grade_not_found', ['grade' => $row->grade]));

                    continue;
                }
                $data['resolvedSectionId'] = $sectionId;

                if ($row->classRoom !== null) {
                    $classId = $classMap[$sectionId.'|'.$this->norm($row->classRoom)] ?? null;
                    if (! $classId) {
                        $out[] = $this->invalid($data, __('student_import.errors.class_not_found', ['class' => $row->classRoom]));

                        continue;
                    }
                    $data['resolvedClassId'] = $classId;
                }
            } elseif ($row->classRoom !== null) {
                // Class without a grade can't be placed.
                $out[] = $this->invalid($data, __('student_import.errors.class_without_grade'));

                continue;
            }

            // 4. Explicit username collision (against any other user).
            if ($row->username !== null) {
                $clashes = User::where('username', $row->username)
                    ->when($existingId, fn ($q) => $q->where('id', '!=', $existingId))
                    ->exists();
                if ($clashes) {
                    $out[] = $this->invalid($data, __('student_import.errors.username_taken', ['username' => $row->username]));

                    continue;
                }
            }

            // 5. New vs update.
            $data['status'] = $existingId ? 'update' : 'new';
            $data['reason'] = null;
            $out[] = $data;
        }

        return $out;
    }

    /** @return string|null the Arabic label of the first missing required field */
    private function missingRequired(StudentImportRowDto $row): ?string
    {
        $checks = [
            'identity' => $row->nationalId,
            'first_name' => $row->firstName,
            'last_name' => $row->lastName,
            'father_name' => $row->fatherName,
        ];
        foreach ($checks as $key => $value) {
            if ($value === null || trim((string) $value) === '') {
                return __('student_import.fields.'.$key);
            }
        }

        return null;
    }

    private function invalid(array $data, string $reason, string $status = 'invalid'): array
    {
        $data['status'] = $status;
        $data['reason'] = $reason;

        return $data;
    }

    private function norm(?string $v): string
    {
        $v = trim((string) $v);
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        return mb_strtolower($v);
    }
}
