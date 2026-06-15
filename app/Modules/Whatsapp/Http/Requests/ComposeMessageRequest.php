<?php

namespace App\Modules\Whatsapp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComposeMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorisation is enforced in the controller via canDo('whatsapp.send').
        return true;
    }

    public function rules(): array
    {
        return [
            'message_type' => ['required', Rule::in(['text', 'image', 'pdf'])],

            'body' => [
                Rule::requiredIf(fn () => $this->input('message_type') === 'text'),
                'nullable',
                'string',
                'max:4000',
            ],

            'image' => [
                Rule::requiredIf(fn () => $this->input('message_type') === 'image'),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120', // 5 MB
            ],

            'pdf' => [
                Rule::requiredIf(fn () => $this->input('message_type') === 'pdf'),
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240', // 10 MB
            ],

            'audience'       => ['required', 'string'],
            'ref_id'         => ['nullable', 'integer'],
            'recipient_ids'   => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required'          => 'نص الرسالة مطلوب للرسائل النصية.',
            'image.required'         => 'يجب رفع صورة.',
            'image.mimes'            => 'صيغة الصورة غير مدعومة (jpg, jpeg, png, webp).',
            'image.max'              => 'حجم الصورة يتجاوز الحد المسموح (5 ميجابايت).',
            'pdf.required'           => 'يجب رفع ملف PDF.',
            'pdf.mimes'              => 'الملف يجب أن يكون بصيغة PDF.',
            'pdf.max'                => 'حجم الملف يتجاوز الحد المسموح (10 ميجابايت).',
            'recipient_ids.required' => 'يجب اختيار مستلم واحد على الأقل.',
            'recipient_ids.min'      => 'يجب اختيار مستلم واحد على الأقل.',
        ];
    }

    /**
     * Sanitised message body (strip tags / control chars) to neutralise
     * injected markup before it is persisted/sent.
     */
    public function cleanBody(): ?string
    {
        $body = $this->input('body');
        if ($body === null) {
            return null;
        }

        $body = strip_tags((string) $body);
        // drop control chars except newline/tab
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body);

        return trim((string) $body);
    }
}
