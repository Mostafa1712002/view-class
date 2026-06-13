<?php

namespace App\Modules\Surveys\Http\Requests;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'                    => ['required', 'string', 'max:200'],
            'description'              => ['nullable', 'string'],
            'audience'                 => ['required', Rule::in(Survey::AUDIENCES)],
            'status'                   => ['required', Rule::in(Survey::STATUSES)],
            'starts_at'                => ['nullable', 'date'],
            'ends_at'                  => ['nullable', 'date', 'after_or_equal:starts_at'],
            'questions'                => ['required', 'array', 'min:1'],
            'questions.*.text'         => ['required', 'string'],
            'questions.*.type'         => ['required', Rule::in(SurveyQuestion::TYPES)],
            'questions.*.options'      => ['nullable', 'array'],
            'questions.*.options.*'    => ['nullable', 'string', 'max:255'],
            'questions.*.is_required'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'             => 'عنوان الاستبيان مطلوب.',
            'title.max'                  => 'عنوان الاستبيان لا يتجاوز 200 حرف.',
            'audience.required'          => 'يرجى تحديد الفئة المستهدفة.',
            'audience.in'                => 'الفئة المستهدفة غير صحيحة.',
            'status.required'            => 'يرجى تحديد حالة الاستبيان.',
            'status.in'                  => 'حالة الاستبيان غير صحيحة.',
            'starts_at.date'             => 'تاريخ البدء غير صحيح.',
            'ends_at.date'               => 'تاريخ الانتهاء غير صحيح.',
            'ends_at.after_or_equal'     => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء أو مساوياً له.',
            'questions.required'         => 'يجب إضافة سؤال واحد على الأقل.',
            'questions.min'              => 'يجب إضافة سؤال واحد على الأقل.',
            'questions.*.text.required'  => 'نص السؤال مطلوب.',
            'questions.*.type.required'  => 'نوع السؤال مطلوب.',
            'questions.*.type.in'        => 'نوع السؤال غير صحيح.',
        ];
    }
}
