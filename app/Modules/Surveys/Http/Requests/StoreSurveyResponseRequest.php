<?php

namespace App\Modules\Surveys\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'answers'   => ['nullable', 'array'],
            'answers.*' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.array' => 'صيغة الإجابات غير صحيحة.',
        ];
    }
}
