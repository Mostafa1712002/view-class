<?php

namespace App\Modules\Mail\Http\Requests;

use App\Models\InternalMail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $isDraft = $this->input('action') === 'draft';

        return [
            'subject'            => ['required', 'string', 'max:255'],
            'importance'         => ['required', Rule::in(InternalMail::IMPORTANCES)],
            'body'               => ['required', 'string'],
            'to'                 => $isDraft ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'to.*'               => ['nullable', 'integer', 'exists:users,id'],
            'related_student_id' => ['nullable', 'integer', 'exists:users,id'],
            'attachment'         => ['nullable', 'file', 'max:10240'],
            'action'             => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required'    => 'الموضوع مطلوب.',
            'subject.max'         => 'الموضوع يجب ألا يتجاوز 255 حرفاً.',
            'importance.required' => 'الأهمية مطلوبة.',
            'importance.in'       => 'قيمة الأهمية غير صحيحة.',
            'body.required'       => 'نص الرسالة مطلوب.',
            'to.required'         => 'يجب اختيار مستلم واحد على الأقل.',
            'to.min'              => 'يجب اختيار مستلم واحد على الأقل.',
            'to.*.exists'         => 'أحد المستلمين المختارين غير موجود.',
            'attachment.file'     => 'الملف المرفق غير صالح.',
            'attachment.max'      => 'حجم المرفق يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }
}
