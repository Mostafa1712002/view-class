<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionInfoSection extends Model
{
    protected $table = 'admission_info_sections';

    protected $fillable = [
        'school_id', 'title', 'content', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Default registration-info sections (card "الأقسام الافتراضية"). */
    public static function defaults(): array
    {
        return [
            'مستندات وشروط التسجيل',
            'طرق تسديد الرسوم',
            'لوائح وأنظمة المدارس',
            'النقل والمواصلات',
            'العروض والاشتراكات',
            'التواصل والملاحظات',
        ];
    }
}
