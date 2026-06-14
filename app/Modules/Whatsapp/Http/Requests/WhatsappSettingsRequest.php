<?php

namespace App\Modules\Whatsapp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhatsappSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'whatsapp_number'        => ['nullable', 'string', 'max:30'],
            'provider'               => ['required', 'in:log,http'],
            'api_token'              => ['nullable', 'string', 'max:500'],
            'api_url'                => ['nullable', 'url', 'max:255'],
            'is_enabled'             => ['sometimes', 'boolean'],
            'send_on_day_absence'    => ['sometimes', 'boolean'],
            'send_on_period_absence' => ['sometimes', 'boolean'],
            'send_on_late'           => ['sometimes', 'boolean'],
            'send_on_edit'           => ['sometimes', 'boolean'],
            'send_on_excuse_accepted' => ['sometimes', 'boolean'],
            'send_on_excuse_rejected' => ['sometimes', 'boolean'],
            'template_absence'        => ['nullable', 'string'],
            'template_late'           => ['nullable', 'string'],
            'template_excuse_accepted' => ['nullable', 'string'],
            'template_excuse_rejected' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'whatsapp_number'         => 'رقم واتساب المدرسة',
            'provider'                => 'مزود الخدمة',
            'api_token'               => 'رمز الوصول (API Token)',
            'api_url'                 => 'رابط API',
            'is_enabled'              => 'تفعيل الإشعارات',
            'template_absence'        => 'قالب رسالة الغياب',
            'template_late'           => 'قالب رسالة التأخر',
            'template_excuse_accepted' => 'قالب قبول العذر',
            'template_excuse_rejected' => 'قالب رفض العذر',
        ];
    }

    /**
     * Normalise checkboxes: unchecked checkboxes are absent from POST, cast them to false.
     */
    protected function prepareForValidation(): void
    {
        $booleans = [
            'is_enabled', 'send_on_day_absence', 'send_on_period_absence',
            'send_on_late', 'send_on_edit', 'send_on_excuse_accepted', 'send_on_excuse_rejected',
        ];

        foreach ($booleans as $field) {
            $this->merge([$field => $this->boolean($field)]);
        }
    }
}
