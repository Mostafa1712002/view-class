<?php

namespace App\Modules\QuestionBankCore\Actions\Import;

use App\Models\AcademicTerm;
use App\Models\StudyWeek;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * #248 — parses the skills import xlsx into normalized, validated rows BEFORE any
 * DB write. Each returned row carries:
 *   - rowNumber : 1-based Excel row (data starts at 2)
 *   - status    : valid | invalid
 *   - errors    : Arabic messages
 *   - raw       : original cell values (for the preview / error display)
 *   - payload   : the resolved Skill attributes (only when valid)
 *
 * Lookups (subject/semester/week) are resolved by NAME within the caller's school
 * scope; an unknown name makes the row invalid (no silent skip). Mirrors the
 * questions importer's name-matching approach.
 */
final class ParseSkillsExcel
{
    /** Header columns (card spec), in order. */
    public const COLUMNS = [
        'skill_name', 'subject', 'semester', 'week', 'compound', 'school',
        'school_type', 'grade', 'class', 'skill_type', 'is_tahsili', 'is_ability', 'status',
    ];

    private const SKILL_TYPES = ['normal', 'ability', 'tahsili', 'verbal', 'quantitative'];

    private const SKILL_TYPE_ALIASES = [
        'عادية' => 'normal', 'عادي' => 'normal', 'normal' => 'normal',
        'قدرات' => 'ability', 'ability' => 'ability',
        'تحصيلي' => 'tahsili', 'tahsili' => 'tahsili',
        'لفظي' => 'verbal', 'verbal' => 'verbal',
        'كمي' => 'quantitative', 'quantitative' => 'quantitative',
    ];

    private array $subjects = [];

    private array $semesters = [];

    private array $weeks = [];

    /**
     * @return array<int,array<string,mixed>>
     */
    public function execute(UploadedFile $file, ?int $schoolId): array
    {
        $this->loadLookups($schoolId);

        $sheet = IOFactory::load($file->getRealPath())->getSheet(0);
        $rows = $sheet->toArray(null, true, true, false); // 0-indexed columns

        if (count($rows) < 2) {
            return [];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $colIndex = [];
        foreach (self::COLUMNS as $name) {
            $idx = array_search($name, $header, true);
            $colIndex[$name] = $idx === false ? null : $idx;
        }

        $parsed = [];
        for ($r = 1; $r < count($rows); $r++) {
            $raw = $rows[$r];
            // skip fully-empty rows
            if (count(array_filter($raw, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $parsed[] = $this->parseRow($r + 1, $raw, $colIndex, $schoolId);
        }

        return $parsed;
    }

    private function parseRow(int $rowNumber, array $raw, array $colIndex, ?int $schoolId): array
    {
        $get = fn (string $name) => $colIndex[$name] === null ? '' : trim((string) ($raw[$colIndex[$name]] ?? ''));
        $errors = [];

        $name = $get('skill_name');
        if ($name === '') {
            $errors[] = 'اسم المهارة مطلوب.';
        }

        // subject (required, resolved by name)
        $subjectName = $get('subject');
        $subjectId = null;
        if ($subjectName === '') {
            $errors[] = 'المادة مطلوبة.';
        } else {
            $subjectId = $this->subjects[$this->norm($subjectName)] ?? null;
            if ($subjectId === null) {
                $errors[] = "المادة «{$subjectName}» غير موجودة في النظام.";
            }
        }

        // semester (optional, by name)
        $semesterName = $get('semester');
        $semesterId = null;
        if ($semesterName !== '') {
            $semesterId = $this->semesters[$this->norm($semesterName)] ?? null;
            if ($semesterId === null) {
                $errors[] = "الفصل الدراسي «{$semesterName}» غير موجود.";
            }
        }

        // week (optional, by name)
        $weekName = $get('week');
        $weekId = null;
        if ($weekName !== '') {
            $weekId = $this->weeks[$this->norm($weekName)] ?? null;
            if ($weekId === null) {
                $errors[] = "الأسبوع «{$weekName}» غير موجود.";
            }
        }

        // skill_type (optional, defaults normal)
        $typeRaw = $this->norm($get('skill_type'));
        $skillType = $typeRaw === '' ? 'normal' : ($this->SKILL_TYPE_ALIASES_lookup($typeRaw));
        if ($skillType === null) {
            $errors[] = 'نوع المهارة غير صحيح (عادية/قدرات/تحصيلي/لفظي/كمي).';
            $skillType = 'normal';
        }

        $isTahsili = $this->bool($get('is_tahsili')) || $skillType === 'tahsili';
        $isAbility = $this->bool($get('is_ability')) || $skillType === 'ability';

        $statusRaw = $this->norm($get('status'));
        $status = in_array($statusRaw, ['inactive', 'غير نشط', 'معطل'], true) ? 'inactive' : 'active';

        $valid = $errors === [];

        return [
            'rowNumber' => $rowNumber,
            'status'    => $valid ? 'valid' : 'invalid',
            'errors'    => $errors,
            'raw'       => [
                'skill_name' => $name, 'subject' => $subjectName, 'semester' => $semesterName,
                'week' => $weekName, 'skill_type' => $get('skill_type'),
            ],
            'payload'   => $valid ? [
                'school_id'   => $schoolId,
                'name'        => $name,
                'subject_id'  => $subjectId,
                'semester_id' => $semesterId,
                'week_id'     => $weekId,
                'skill_type'  => $skillType,
                'is_tahsili'  => $isTahsili,
                'is_ability'  => $isAbility,
                'status'      => $status,
            ] : null,
        ];
    }

    private function loadLookups(?int $schoolId): void
    {
        Subject::query()
            ->when($schoolId !== null, fn ($q) => $q->where(fn ($w) => $w->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->get(['id', 'name', 'name_en'])
            ->each(function ($s) {
                $this->subjects[$this->norm($s->name)] = $s->id;
                if ($s->name_en) {
                    $this->subjects[$this->norm($s->name_en)] = $s->id;
                }
            });

        $termIds = [];
        AcademicTerm::query()
            ->when($schoolId !== null, fn ($q) => $q->whereHas('academicYear', fn ($w) => $w->where('school_id', $schoolId)))
            ->get(['id', 'name'])
            ->each(function ($t) use (&$termIds) {
                $this->semesters[$this->norm($t->name)] = $t->id;
                $termIds[] = $t->id;
            });

        StudyWeek::query()
            ->when($termIds !== [], fn ($q) => $q->whereIn('academic_term_id', $termIds))
            ->get(['id', 'name'])
            ->each(fn ($w) => $this->weeks[$this->norm($w->name)] = $w->id);
    }

    private function SKILL_TYPE_ALIASES_lookup(string $key): ?string
    {
        return self::SKILL_TYPE_ALIASES[$key] ?? (in_array($key, self::SKILL_TYPES, true) ? $key : null);
    }

    private function bool(string $v): bool
    {
        return in_array($this->norm($v), ['1', 'true', 'yes', 'نعم', 'صح'], true);
    }

    private function norm(string $v): string
    {
        return mb_strtolower(trim($v));
    }
}
