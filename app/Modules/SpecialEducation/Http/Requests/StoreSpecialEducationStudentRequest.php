<?php

namespace App\Modules\SpecialEducation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpecialEducationStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $schoolId = auth()->user()?->school_id ?? 0;

        return [
            'student_id'          => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('school_id', $schoolId),
            ],
            'category'            => ['required', 'string', 'in:learning_disability,gifted,speech,physical,behavioral,visual,hearing,other'],
            'diagnosis'           => ['nullable', 'string'],
            'severity'            => ['nullable', 'string', 'in:mild,moderate,severe'],
            'assigned_specialist' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('school_id', $schoolId),
            ],
            'status'              => ['required', 'string', 'in:active,inactive,graduated'],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
