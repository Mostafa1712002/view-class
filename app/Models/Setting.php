<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'school_id',
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function getValueAttribute($value)
    {
        return match ($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = match ($this->type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    public static function get(string $key, $default = null, ?int $schoolId = null)
    {
        $cacheKey = "setting_{$schoolId}_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default, $schoolId) {
            $setting = static::where('key', $key)
                ->where('school_id', $schoolId)
                ->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value, string $type = 'string', ?int $schoolId = null, ?string $group = 'general')
    {
        $setting = static::updateOrCreate(
            ['key' => $key, 'school_id' => $schoolId],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        Cache::forget("setting_{$schoolId}_{$key}");

        return $setting;
    }

    public static function getByGroup(string $group, ?int $schoolId = null)
    {
        return static::where('group', $group)
            ->where('school_id', $schoolId)
            ->get()
            ->pluck('value', 'key');
    }

    public static function getDefaults(): array
    {
        return [
            'general' => [
                ['key' => 'school_name', 'value' => '', 'type' => 'string', 'description' => 'اسم المدرسة'],
                ['key' => 'school_email', 'value' => '', 'type' => 'string', 'description' => 'البريد الإلكتروني'],
                ['key' => 'school_phone', 'value' => '', 'type' => 'string', 'description' => 'رقم الهاتف'],
                ['key' => 'school_address', 'value' => '', 'type' => 'string', 'description' => 'العنوان'],
                ['key' => 'school_logo', 'value' => '', 'type' => 'string', 'description' => 'شعار المدرسة'],
            ],
            'academic' => [
                ['key' => 'passing_grade', 'value' => '50', 'type' => 'integer', 'description' => 'درجة النجاح'],
                ['key' => 'max_absent_days', 'value' => '15', 'type' => 'integer', 'description' => 'أقصى أيام غياب مسموح'],
                ['key' => 'grading_system', 'value' => 'percentage', 'type' => 'string', 'description' => 'نظام الدرجات'],
            ],
            'notifications' => [
                ['key' => 'email_notifications', 'value' => '1', 'type' => 'boolean', 'description' => 'إشعارات البريد'],
                ['key' => 'sms_notifications', 'value' => '0', 'type' => 'boolean', 'description' => 'إشعارات SMS'],
                ['key' => 'parent_grade_notification', 'value' => '1', 'type' => 'boolean', 'description' => 'إشعار ولي الأمر بالدرجات'],
                ['key' => 'parent_absence_notification', 'value' => '1', 'type' => 'boolean', 'description' => 'إشعار ولي الأمر بالغياب'],
            ],
        ];
    }
}
