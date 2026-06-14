<?php

namespace App\Modules\Whatsapp\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolWhatsappSetting extends Model
{
    use SoftDeletes;

    protected $table = 'school_whatsapp_settings';

    protected $fillable = [
        'school_id',
        'whatsapp_number',
        'provider',
        'api_token',
        'api_url',
        'is_enabled',
        'send_on_day_absence',
        'send_on_period_absence',
        'send_on_late',
        'send_on_edit',
        'send_on_excuse_accepted',
        'send_on_excuse_rejected',
        'template_absence',
        'template_late',
        'template_excuse_accepted',
        'template_excuse_rejected',
    ];

    protected $casts = [
        'is_enabled'              => 'boolean',
        'send_on_day_absence'     => 'boolean',
        'send_on_period_absence'  => 'boolean',
        'send_on_late'            => 'boolean',
        'send_on_edit'            => 'boolean',
        'send_on_excuse_accepted' => 'boolean',
        'send_on_excuse_rejected' => 'boolean',
        'api_token'               => 'encrypted',
    ];

    /**
     * All supported template placeholders with human-readable descriptions.
     */
    public const PLACEHOLDERS = [
        '{student_name}' => 'اسم الطالب',
        '{date}'         => 'التاريخ',
        '{absence_type}' => 'نوع الغياب (يومي / حصة)',
        '{subject_name}' => 'اسم المادة',
        '{period_name}'  => 'رقم الحصة',
        '{school_name}'  => 'اسم المدرسة',
        '{brand}'        => 'اسم المنصة',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
