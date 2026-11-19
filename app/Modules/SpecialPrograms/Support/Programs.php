<?php

namespace App\Modules\SpecialPrograms\Support;

final class Programs
{
    public const PROGRAMS = [
        'qiyas' => ['label_ar' => 'القدرات', 'label_en' => 'Qiyas', 'grade_levels' => [1, 2, 3, 4, 5, 6, 7, 8, 9]],
        'tahsili' => ['label_ar' => 'التحصيلي', 'label_en' => 'Tahsili', 'grade_levels' => [3, 9, 12]],
        'nafis' => ['label_ar' => 'نافس', 'label_en' => 'Nafis', 'grade_levels' => [3, 6]],
    ];

    /** Level bands per program, ordered best → worst: [min, max]. Keys match PerformanceLevels::LEVELS. */
    public const DEFAULT_BANDS = [
        'tahsili' => ['excellent' => [95, 100], 'very_good' => [85, 94], 'good' => [75, 84], 'acceptable' => [65, 74], 'weak' => [0, 64]],
        'nafis' => ['excellent' => [85, 100], 'very_good' => [75, 84], 'good' => [65, 74], 'acceptable' => [50, 64], 'weak' => [0, 49]],
        'qiyas' => ['excellent' => [90, 100], 'very_good' => [80, 89], 'good' => [70, 79], 'acceptable' => [60, 69], 'weak' => [0, 59]],
    ];

    public static function keys(): array
    {
        return array_keys(self::PROGRAMS);
    }

    public static function label(string $program): string
    {
        $p = self::PROGRAMS[$program] ?? null;

        return $p ? (app()->getLocale() === 'en' ? $p['label_en'] : $p['label_ar']) : $program;
    }

    public static function defaultGradeLevels(string $program): array
    {
        return self::PROGRAMS[$program]['grade_levels'] ?? [];
    }
}
