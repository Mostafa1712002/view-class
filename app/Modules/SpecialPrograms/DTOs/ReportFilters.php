<?php

namespace App\Modules\SpecialPrograms\DTOs;

use Illuminate\Http\Request;

final class ReportFilters
{
    public function __construct(
        public readonly ?int $school = null,
        public readonly ?string $program = null,
        public readonly ?int $grade = null,
        public readonly ?int $class = null,
        public readonly ?int $group = null,
        public readonly ?int $subject = null,
        public readonly ?string $origin = null,   // admin | teacher
        public readonly ?string $from = null,     // Y-m-d
        public readonly ?string $to = null,       // Y-m-d
        public readonly ?int $exam = null,
        public readonly ?int $teacherId = null,
    ) {}

    /** $forced overrides request input (server-decided scope: school, program, teacherId). */
    public static function fromRequest(Request $request, array $forced = []): self
    {
        $int = fn (string $k) => ($v = $request->input($k)) !== null && $v !== '' ? (int) $v : null;
        $str = fn (string $k) => ($v = $request->input($k)) !== null && $v !== '' ? (string) $v : null;
        $origin = $str('origin');
        $values = [
            'school' => $int('school_id'),
            'program' => $str('program'),
            'grade' => $int('grade'),
            'class' => $int('class_id'),
            'group' => $int('group_id'),
            'subject' => $int('subject_id'),
            'origin' => in_array($origin, ['admin', 'teacher'], true) ? $origin : null,
            'from' => $str('from'),
            'to' => $str('to'),
            'exam' => $int('exam_id'),
            'teacherId' => null,
        ];

        return new self(...array_merge($values, $forced));
    }
}
