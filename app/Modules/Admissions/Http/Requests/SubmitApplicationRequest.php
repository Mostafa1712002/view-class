<?php

namespace App\Modules\Admissions\Http\Requests;

use App\Modules\Admissions\Services\AdmissionSettingsService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Public (guest) application submission. Required-field rules are derived
 * dynamically from the school's configured admission_form_fields so the form
 * settings actually drive validation (card "التحقق من الحقول الإجبارية").
 */
class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public form, no auth
    }

    public function rules(): array
    {
        // Map dynamic field keys → the explicit application columns / data bag.
        $columnFields = [
            'student_name', 'guardian_name', 'phone', 'email', 'national_id',
            'hijri_code', 'birth_date', 'city', 'nationality', 'address',
            'stage', 'grade',
        ];

        $rules = [
            'student_name'  => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:32'],
            'email'         => ['nullable', 'email', 'max:255'],
            'national_id'   => ['nullable', 'string', 'max:32'],
            'hijri_code'    => ['nullable', 'string', 'max:32'],
            'birth_date'    => ['nullable', 'date'],
            'city'          => ['nullable', 'string', 'max:255'],
            'nationality'   => ['nullable', 'string', 'max:255'],
            'address'       => ['nullable', 'string', 'max:1000'],
            'stage'         => ['nullable', 'string', 'max:255'],
            'grade'         => ['nullable', 'string', 'max:255'],
            'website'       => ['nullable', 'size:0'], // honeypot: must stay empty
            'data'          => ['nullable', 'array'],
            'data.*'        => ['nullable', 'string', 'max:2000'],
        ];

        $schoolId = (int) $this->route('school')?->id ?: (int) $this->input('school_id');
        if ($schoolId) {
            $service = app(AdmissionSettingsService::class);
            foreach ($service->visibleFields($schoolId) as $field) {
                if (! $field->is_required) {
                    continue;
                }
                $key = in_array($field->field_key, $columnFields, true)
                    ? $field->field_key
                    : "data.{$field->field_key}";
                $rules[$key] = array_values(array_unique(
                    array_merge(['required'], (array) ($rules[$key] ?? ['string', 'max:2000']))
                ));
                // strip a leading 'nullable' if present
                $rules[$key] = array_values(array_filter($rules[$key], fn ($r) => $r !== 'nullable'));
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'website.size' => 'تعذّر إرسال الطلب.',
            'required'      => 'هذا الحقل مطلوب.',
            'email'         => 'صيغة البريد الإلكتروني غير صحيحة.',
        ];
    }
}
