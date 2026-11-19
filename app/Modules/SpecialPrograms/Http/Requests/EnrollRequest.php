<?php

namespace App\Modules\SpecialPrograms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware carries special_programs.manage
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
            'all_students' => ['nullable', 'boolean'],
            'class_id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
        ];
    }
}
