<?php

namespace App\Modules\Lessons\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester' => ['required', 'in:first,second'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'period_number' => ['required', 'integer', 'between:1,12'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_id.required' => trans('lessons_admin.validation.class_required'),
            'class_id.exists' => trans('lessons_admin.validation.class_invalid'),
            'academic_year_id.required' => trans('lessons_admin.validation.year_required'),
            'semester.required' => trans('lessons_admin.validation.semester_required'),
            'subject_id.required' => trans('lessons_admin.validation.subject_required'),
            'teacher_id.required' => trans('lessons_admin.validation.teacher_required'),
            'day_of_week.required' => trans('lessons_admin.validation.day_required'),
            'period_number.required' => trans('lessons_admin.validation.period_required'),
            'end_time.after' => trans('lessons_admin.validation.end_after_start'),
        ];
    }
}
