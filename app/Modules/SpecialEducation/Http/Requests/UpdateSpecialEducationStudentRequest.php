<?php

namespace App\Modules\SpecialEducation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecialEducationStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $schoolId  = auth()->user()?->school_id ?? 0;
        $studentId = (int) $this->route('id');

        return [
            'student_id'          => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('school_id', $schoolId),
                Rule::unique('special_education_students', 'student_id')
                    ->where('school_id', $schoolId)
                    ->ignore($studentId),
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
