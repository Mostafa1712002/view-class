<?php

namespace App\Modules\Promotion\Support;

/**
 * The 12 fixed Saudi grades. A lookup constant, not a DB table — the list never
 * changes and needs no admin CRUD (design.md decision). Single source of truth
 * for the grade-create dropdown, the subjects create page, and import validation.
 *
 *   ordinal 1..6  → الابتدائي (primary)
 *   ordinal 7..9  → المتوسط  (intermediate)
 *   ordinal 10..12→ الثانوي  (secondary)
 *
 * `name`  = full grade name written onto the grade (section) record.
 * `label` = short ordinal+stage label for dropdowns (subjects, import).
 */
final class StandardGrades
{
    /** @var array<int,array{level:string,name:string,label:string}> */
    public const GRADES = [
        1  => ['level' => 'primary',      'name' => 'الصف الأول الابتدائي',   'label' => 'الأول الابتدائي'],
        2  => ['level' => 'primary',      'name' => 'الصف الثاني الابتدائي',  'label' => 'الثاني الابتدائي'],
        3  => ['level' => 'primary',      'name' => 'الصف الثالث الابتدائي',  'label' => 'الثالث الابتدائي'],
        4  => ['level' => 'primary',      'name' => 'الصف الرابع الابتدائي',  'label' => 'الرابع الابتدائي'],
        5  => ['level' => 'primary',      'name' => 'الصف الخامس الابتدائي',  'label' => 'الخامس الابتدائي'],
        6  => ['level' => 'primary',      'name' => 'الصف السادس الابتدائي',  'label' => 'السادس الابتدائي'],
        7  => ['level' => 'intermediate', 'name' => 'الصف الأول المتوسط',     'label' => 'الأول المتوسط'],
        8  => ['level' => 'intermediate', 'name' => 'الصف الثاني المتوسط',    'label' => 'الثاني المتوسط'],
        9  => ['level' => 'intermediate', 'name' => 'الصف الثالث المتوسط',    'label' => 'الثالث المتوسط'],
        10 => ['level' => 'secondary',    'name' => 'الصف الأول الثانوي',     'label' => 'الأول الثانوي'],
        11 => ['level' => 'secondary',    'name' => 'الصف الثاني الثانوي',    'label' => 'الثاني الثانوي'],
        12 => ['level' => 'secondary',    'name' => 'الصف الثالث الثانوي',    'label' => 'الثالث الثانوي'],
    ];

    /** @return array<int,array{level:string,name:string,label:string}> */
    public static function all(): array
    {
        return self::GRADES;
    }

    public static function has(int $ordinal): bool
    {
        return isset(self::GRADES[$ordinal]);
    }

    /** @return array{level:string,name:string,label:string}|null */
    public static function get(int $ordinal): ?array
    {
        return self::GRADES[$ordinal] ?? null;
    }

    public static function name(int $ordinal): ?string
    {
        return self::GRADES[$ordinal]['name'] ?? null;
    }

    public static function level(int $ordinal): ?string
    {
        return self::GRADES[$ordinal]['level'] ?? null;
    }

    public static function label(int $ordinal): ?string
    {
        return self::GRADES[$ordinal]['label'] ?? null;
    }

    /**
     * ordinal => short label, for the subjects + import grade dropdowns.
     *
     * @return array<int,string>
     */
    public static function options(): array
    {
        return array_map(fn ($g) => $g['label'], self::GRADES);
    }

    /**
     * True when the value matches a standard grade — full name or short label,
     * whitespace/case-insensitive. Used to flag non-standard import rows.
     */
    public static function isStandardName(?string $value): bool
    {
        $needle = self::norm($value);
        if ($needle === '') {
            return false;
        }
        foreach (self::GRADES as $g) {
            if (self::norm($g['name']) === $needle || self::norm($g['label']) === $needle) {
                return true;
            }
        }

        return false;
    }

    private static function norm(?string $v): string
    {
        $v = trim((string) $v);
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        return mb_strtolower($v);
    }
}
