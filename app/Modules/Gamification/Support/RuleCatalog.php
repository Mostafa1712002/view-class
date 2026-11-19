<?php

namespace App\Modules\Gamification\Support;

/** The rule catalog lives in code; DB rows (point_rule_settings) only override its defaults. */
final class RuleCatalog
{
    public const STUDENT = 'student';

    public const TEACHER = 'teacher';

    public const VICE = 'vice';

    public const PER_CORRECT_ANSWER = 'per_correct_answer';

    public const RATIO = 'ratio';

    public const PER_EVENT = 'per_event';

    /** key => [beneficiary, calc_mode, wired, options schema]. Labels: lang/<locale>/points.php rules.<key>. */
    public const RULES = [
        'student.correct_answers.alawwal' => ['beneficiary' => 'student', 'calc_mode' => 'per_correct_answer', 'wired' => false, 'options' => []],
        'student.correct_answers.alawwal_plus' => ['beneficiary' => 'student', 'calc_mode' => 'per_correct_answer', 'wired' => false, 'options' => []],
        // Awarded by card #368 through SyncActivityPoints; nothing here hooks them.
        'student.correct_answers.ana_wal_qudurat' => ['beneficiary' => 'student', 'calc_mode' => 'per_correct_answer', 'wired' => false, 'options' => []],
        'student.correct_answers.nafes' => ['beneficiary' => 'student', 'calc_mode' => 'per_correct_answer', 'wired' => false, 'options' => []],
        'student.correct_answers.tahsili' => ['beneficiary' => 'student', 'calc_mode' => 'per_correct_answer', 'wired' => false, 'options' => []],
        'student.correct_answers.subjects' => ['beneficiary' => 'student', 'calc_mode' => 'per_correct_answer', 'wired' => true, 'options' => []],
        'student.assignments' => ['beneficiary' => 'student', 'calc_mode' => 'ratio', 'wired' => true, 'options' => []],
        'student.portfolio_approved' => ['beneficiary' => 'student', 'calc_mode' => 'per_event', 'wired' => false, 'options' => []],
        'teacher.portfolio_approved' => ['beneficiary' => 'teacher', 'calc_mode' => 'per_event', 'wired' => false, 'options' => []],
        'teacher.assignments' => ['beneficiary' => 'teacher', 'calc_mode' => 'ratio', 'wired' => true,
            'options' => ['basis' => ['type' => 'select', 'values' => ['interaction', 'correct'], 'default' => 'interaction']]],
        'teacher.exams' => ['beneficiary' => 'teacher', 'calc_mode' => 'ratio', 'wired' => true,
            'options' => ['basis' => ['type' => 'select', 'values' => ['interaction', 'correct'], 'default' => 'interaction']]],
        'teacher.discussion_participation' => ['beneficiary' => 'teacher', 'calc_mode' => 'ratio', 'wired' => true, 'options' => []],
        'teacher.live_rooms_attendance' => ['beneficiary' => 'teacher', 'calc_mode' => 'ratio', 'wired' => true, 'options' => []],
        'teacher.motivation' => ['beneficiary' => 'teacher', 'calc_mode' => 'per_event', 'wired' => false, 'options' => []],
        'teacher.media_video' => ['beneficiary' => 'teacher', 'calc_mode' => 'ratio', 'wired' => false, 'options' => []],
        'teacher.media_worksheet' => ['beneficiary' => 'teacher', 'calc_mode' => 'ratio', 'wired' => false, 'options' => []],
        'teacher.preparation' => ['beneficiary' => 'teacher', 'calc_mode' => 'per_event', 'wired' => true, 'options' => []],
        'vice.job_performance_on_time' => ['beneficiary' => 'vice', 'calc_mode' => 'per_event', 'wired' => true, 'options' => []],
        'vice.class_visits' => ['beneficiary' => 'vice', 'calc_mode' => 'per_event', 'wired' => true,
            'options' => [
                'visits_per_term' => ['type' => 'int', 'default' => null],
                'window_start' => ['type' => 'date', 'default' => null],
                'window_end' => ['type' => 'date', 'default' => null],
            ]],
        'vice.school_schedule_on_time' => ['beneficiary' => 'vice', 'calc_mode' => 'per_event', 'wired' => true,
            'options' => ['deadline' => ['type' => 'datetime', 'default' => null]]],
    ];

    public static function get(string $key): ?array
    {
        return self::RULES[$key] ?? null;
    }

    /** Rule keys contain dots, so __('points.rules.<key>') can't reach them; read the array instead. */
    public static function label(string $key): string
    {
        return (string) (trans('points.rules')[$key] ?? ($key === 'manual' ? trans('points.kinds.manual') : $key));
    }

    public static function has(string $key): bool
    {
        return isset(self::RULES[$key]);
    }

    /** @return array<string, array> rules of one beneficiary (student|teacher|vice), in catalog order */
    public static function forBeneficiary(string $beneficiary): array
    {
        return array_filter(self::RULES, fn (array $r) => $r['beneficiary'] === $beneficiary);
    }
}
