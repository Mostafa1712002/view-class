<?php

namespace App\Modules\SmsServices\Support;

use App\Models\School;
use App\Models\User;

/**
 * Resolves {placeholder} variables in an SMS template body against a recipient
 * (Trello #238). Used at send time so the persisted message body is the final
 * text after substitution.
 */
final class SmsTemplateRenderer
{
    /**
     * The variables surfaced as insert-buttons in the template editor.
     * key (stored as {key}) => Arabic label.
     */
    public const VARIABLES = [
        'first_name'      => 'الاسم الأول',
        'last_name'       => 'الاسم الأخير',
        'username'        => 'اسم المستخدم',
        'school_name'     => 'اسم المدرسة',
        'full_name'       => 'الاسم الكامل',
        'date'            => 'التاريخ',
        'password'        => 'كلمة المرور',
        'national_id'     => 'رقم الهوية',
        'email'           => 'البريد الإلكتروني',
        'passport_no'     => 'رقم الجواز',
        'grade'           => 'الصف الدراسي',
        'class'           => 'الفصل',
        'student_name'    => 'اسم الطالب',
        'parent_name'     => 'اسم ولي الأمر',
        'teacher_name'    => 'اسم المعلم',
        'subject'         => 'المادة',
        'period'          => 'الحصة',
        'day'             => 'اليوم',
        'check_in'        => 'وقت الحضور',
        'check_out'       => 'وقت الخروج',
        'attendance_state'=> 'حالة الحضور',
        'absence_reason'  => 'سبب الغياب',
        'report_link'     => 'رابط التقرير',
        'platform_link'   => 'رابط المنصة',
        'student_no'      => 'رقم الطالب',
        'mobile'          => 'رقم الجوال',
        'stage'           => 'المرحلة',
        'admin_name'      => 'اسم الإدارة',
    ];

    /**
     * Missing-value behaviour: 'blank' (leave empty), 'dash' (—), 'block'
     * (caller decides not to send). Default = blank.
     */
    public static function render(string $body, array $vars, string $onMissing = 'blank'): string
    {
        return preg_replace_callback('/\{([a-z_]+)\}/', function ($m) use ($vars, $onMissing) {
            $key = $m[1];
            if (array_key_exists($key, $vars) && $vars[$key] !== null && $vars[$key] !== '') {
                return (string) $vars[$key];
            }

            return match ($onMissing) {
                'dash'  => '—',
                default => '',
            };
        }, $body) ?? $body;
    }

    /** Detect whether any placeholder lacks a value (for the 'block' policy). */
    public static function hasMissing(string $body, array $vars): bool
    {
        if (! preg_match_all('/\{([a-z_]+)\}/', $body, $matches)) {
            return false;
        }

        foreach ($matches[1] as $key) {
            if (! array_key_exists($key, self::VARIABLES)) {
                continue; // unknown token left as-is, not "missing"
            }
            if (! array_key_exists($key, $vars) || $vars[$key] === null || $vars[$key] === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the substitution map for a User recipient (student / parent / etc.).
     * Excel rows pass their own map directly instead of using this.
     */
    public static function varsForUser(?User $user, ?School $school = null, array $extra = []): array
    {
        $name   = $user?->name ?? '';
        $parts  = preg_split('/\s+/', trim($name)) ?: [];
        $first  = $parts[0] ?? '';
        $last   = count($parts) > 1 ? end($parts) : '';

        $base = [
            'first_name'   => $first,
            'last_name'    => $last,
            'full_name'    => $name,
            'student_name' => $name,
            'username'     => $user?->username ?? '',
            'national_id'  => $user->national_id ?? '',
            'email'        => $user?->email ?? '',
            'mobile'       => $user?->phone ?? '',
            'student_no'   => (string) ($user?->id ?? ''),
            'school_name'  => $school?->name ?? ($user?->school?->name ?? ''),
            'date'         => now()->format('Y-m-d'),
            'platform_link'=> config('app.url'),
        ];

        return array_merge($base, $extra);
    }
}
