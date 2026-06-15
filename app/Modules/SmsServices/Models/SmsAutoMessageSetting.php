<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsAutoMessageSetting extends Model
{
    protected $table = 'sms_auto_message_settings';

    /**
     * Event types (Trello #241). label + a default template body + whether the
     * trigger integration currently exists in the platform.
     */
    public const EVENT_TYPES = [
        'late_arrival_threshold' => [
            'label'    => 'تنبيه عند تأخر الطالب عن الحضور أكثر من مدة محددة',
            'default'  => 'عزيزي ولي الأمر، نُعلمكم بأن الطالب/ة {student_name} تأخر/ت عن الحضور بتاريخ {date}. {school_name}',
            'has_threshold' => true,
        ],
        'device_offline_admin' => [
            'label'    => 'تنبيه مدير النظام بعدم اتصال أجهزة البصمة',
            'default'  => 'تنبيه: أجهزة البصمة غير متصلة. الرجاء المتابعة. {school_name}',
            'has_threshold' => false,
        ],
        'student_arrival' => [
            'label'    => 'إشعار ولي الأمر عند وصول الطالب إلى المدرسة',
            'default'  => 'عزيزي ولي الأمر، وصل الطالب/ة {student_name} إلى المدرسة الساعة {check_in} بتاريخ {date}. {school_name}',
            'has_threshold' => false,
        ],
        'student_checkin_device' => [
            'label'    => 'إشعار ولي الأمر عند تسجيل دخول الطالب من جهاز البصمة',
            'default'  => 'عزيزي ولي الأمر، سجّل الطالب/ة {student_name} الدخول عبر جهاز البصمة الساعة {check_in}. {school_name}',
            'has_threshold' => false,
        ],
        'student_departure' => [
            'label'    => 'إشعار ولي الأمر عند خروج الطالب من المدرسة',
            'default'  => 'عزيزي ولي الأمر، غادر الطالب/ة {student_name} المدرسة الساعة {check_out} بتاريخ {date}. {school_name}',
            'has_threshold' => false,
        ],
        'departure_without_checkout' => [
            'label'    => 'إشعار عند خروج الطالب بدون تسجيل خروج من جهاز البصمة',
            'default'  => 'عزيزي ولي الأمر، غادر الطالب/ة {student_name} دون تسجيل خروج من جهاز البصمة بتاريخ {date}. {school_name}',
            'has_threshold' => false,
        ],
        'student_leave_request' => [
            'label'    => 'إشعار عند استئذان الطالب من المدرسة',
            'default'  => 'عزيزي ولي الأمر، استأذن الطالب/ة {student_name} من المدرسة بتاريخ {date}. {school_name}',
            'has_threshold' => false,
        ],
    ];

    protected $fillable = [
        'school_id',
        'event_type',
        'is_enabled',
        'template_body',
        'threshold',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function label(): string
    {
        return self::EVENT_TYPES[$this->event_type]['label'] ?? $this->event_type;
    }
}
