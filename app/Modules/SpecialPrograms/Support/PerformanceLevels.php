<?php

namespace App\Modules\SpecialPrograms\Support;

final class PerformanceLevels
{
    /** The 5 fixed levels, best → worst. */
    public const LEVELS = [
        'excellent' => ['name' => 'ممتاز', 'color' => 'success', 'icon' => 'star'],
        'very_good' => ['name' => 'جيد جداً', 'color' => 'info', 'icon' => 'award'],
        'good' => ['name' => 'جيد', 'color' => 'primary', 'icon' => 'check-circle'],
        'acceptable' => ['name' => 'مقبول', 'color' => 'warning', 'icon' => 'dash-circle'],
        'weak' => ['name' => 'ضعيف', 'color' => 'danger', 'icon' => 'x-circle'],
    ];

    /** Default bands of a program, in the repository's band shape (key,name,min,max,color,icon). */
    public static function defaultBands(string $program): array
    {
        $rows = [];
        foreach (Programs::DEFAULT_BANDS[$program] ?? Programs::DEFAULT_BANDS['qiyas'] as $key => [$min, $max]) {
            $rows[] = ['key' => $key, 'min' => $min, 'max' => $max] + self::LEVELS[$key];
        }

        return $rows;
    }

    /** Fills name/color/icon on [key,min,max] rows. */
    public static function hydrate(array $bands): array
    {
        return array_map(fn (array $b) => [
            'key' => $b['key'], 'min' => (int) $b['min'], 'max' => (int) $b['max'],
        ] + (self::LEVELS[$b['key']] ?? ['name' => $b['key'], 'color' => 'secondary', 'icon' => 'circle']), array_values($bands));
    }

    /** The band for a percentage: the highest `min` that is <= pct, else the lowest band. */
    public static function for(float $pct, array $bands): array
    {
        $pct = round($pct, 2);
        usort($bands, fn ($a, $b) => $b['min'] <=> $a['min']);
        foreach ($bands as $band) {
            if ($pct >= $band['min']) {
                return $band;
            }
        }

        return end($bands);
    }

    /** Null when valid, else an Arabic error message. */
    public static function validate(array $bands): ?string
    {
        if (count($bands) !== 5) {
            return 'يجب تحديد 5 مستويات.';
        }
        $keys = array_column($bands, 'key');
        sort($keys);
        $expected = array_keys(self::LEVELS);
        sort($expected);
        if ($keys !== $expected) {
            return 'المستويات غير مكتملة.';
        }
        foreach ($bands as $b) {
            foreach (['min', 'max'] as $f) {
                if (! isset($b[$f]) || filter_var($b[$f], FILTER_VALIDATE_INT) === false || $b[$f] < 0 || $b[$f] > 100) {
                    return 'القيم يجب أن تكون أرقاماً صحيحة بين 0 و 100.';
                }
            }
            if ((int) $b['min'] > (int) $b['max']) {
                return 'الحد الأدنى أكبر من الحد الأعلى.';
            }
        }
        usort($bands, fn ($a, $b) => $a['min'] <=> $b['min']);
        if ((int) $bands[0]['min'] !== 0 || (int) $bands[4]['max'] !== 100) {
            return 'يجب أن تغطي المستويات من 0 إلى 100.';
        }
        for ($i = 1; $i < 5; $i++) {
            if ((int) $bands[$i]['min'] !== (int) $bands[$i - 1]['max'] + 1) {
                return 'المستويات متداخلة أو بها فجوات.';
            }
        }

        return null;
    }
}
