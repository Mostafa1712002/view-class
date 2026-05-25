<?php

namespace App\Modules\NoorImport\Actions;

use App\Models\User;
use App\Modules\NoorImport\DTOs\NoorRowDto;

/**
 * Builds the preview rows for the "معاينة قبل الحفظ" screen.
 *
 * Each row is classified as:
 *   - invalid    : missing/blank national id  → will be skipped on execute
 *   - duplicate  : the same national id appears more than once in THIS file
 *   - update     : a student with this national id already exists in the school
 *   - new        : will be created on execute
 *
 * Returns plain arrays (already JSON-serialisable) carrying the parsed row
 * data plus the computed status + reason so the controller can persist them
 * verbatim and re-use them at execute time without re-parsing the file.
 */
final class ClassifyNoorRows
{
    /**
     * @param  array<int, NoorRowDto>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function execute(array $rows, int $schoolId): array
    {
        // Existing student national ids for this school (single query).
        $ids = array_values(array_filter(array_map(
            fn (NoorRowDto $r) => $r->nationalId,
            $rows
        )));

        $existing = [];
        if ($ids) {
            $existing = User::withTrashed()
                ->where('school_id', $schoolId)
                ->whereIn('national_id', $ids)
                ->pluck('national_id')
                ->map(fn ($v) => (string) $v)
                ->all();
            $existing = array_fill_keys($existing, true);
        }

        $seen = [];
        $out = [];

        foreach ($rows as $row) {
            $data = $row->toArray();
            $nid = $row->nationalId !== null ? trim((string) $row->nationalId) : '';

            if ($nid === '') {
                $data['status'] = 'invalid';
                $data['reason'] = __('noor.errors.missing_id');
                $out[] = $data;
                continue;
            }

            if (isset($seen[$nid])) {
                $data['status'] = 'duplicate';
                $data['reason'] = __('noor.preview.reason_duplicate');
                $out[] = $data;
                continue;
            }
            $seen[$nid] = true;

            if (isset($existing[$nid])) {
                $data['status'] = 'update';
                $data['reason'] = null;
            } else {
                $data['status'] = 'new';
                $data['reason'] = null;
            }
            $out[] = $data;
        }

        return $out;
    }
}
