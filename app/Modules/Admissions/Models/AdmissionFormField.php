<?php

namespace App\Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionFormField extends Model
{
    protected $table = 'admission_form_fields';

    protected $fillable = [
        'school_id', 'field_key', 'label', 'is_visible', 'is_required', 'sort_order',
    ];

    protected $casts = [
        'is_visible'  => 'boolean',
        'is_required' => 'boolean',
        'sort_order'  => 'integer',
    ];

    /**
     * Default form fields seeded lazily per school (card "أمثلة الحقول").
     * student_name / phone are mandatory core fields and always present.
     *
     * @return array<int, array{key:string,label:string,required:bool}>
     */
    public static function defaults(): array
    {
        return [
            ['key' => 'student_name', 'label' => 'اسم الطالب رباعيًا', 'required' => true],
            ['key' => 'national_id',  'label' => 'رقم الهوية',          'required' => true],
            ['key' => 'birth_date',   'label' => 'تاريخ الميلاد',       'required' => false],
            ['key' => 'hijri_code',   'label' => 'تاريخ الميلاد بالهجري', 'required' => false],
            ['key' => 'guardianship', 'label' => 'إثبات الولاية',       'required' => false],
            ['key' => 'father_name',  'label' => 'اسم الأب',            'required' => false],
            ['key' => 'mother_name',  'label' => 'اسم الأم',            'required' => false],
            ['key' => 'guardian_name','label' => 'اسم ولي الأمر رباعيًا', 'required' => true],
            ['key' => 'email',        'label' => 'الإيميل',             'required' => false],
            ['key' => 'phone',        'label' => 'رقم الجوال',          'required' => true],
            ['key' => 'address',      'label' => 'العنوان',             'required' => false],
            ['key' => 'nationality',  'label' => 'الجنسية',             'required' => false],
            ['key' => 'city',         'label' => 'المدينة',             'required' => false],
            ['key' => 'requested_school', 'label' => 'المدرسة المطلوبة', 'required' => false],
            ['key' => 'stage',        'label' => 'المرحلة',             'required' => false],
            ['key' => 'grade',        'label' => 'الصف',                'required' => false],
            ['key' => 'documents',    'label' => 'المستندات',           'required' => false],
            ['key' => 'notes',        'label' => 'ملاحظات',             'required' => false],
        ];
    }
}
