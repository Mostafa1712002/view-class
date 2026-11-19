<?php

namespace App\Modules\SpecialPrograms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Shape only; limits and ownership are enforced by CreateProgramExam before any insert. */
class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware: role + permission:special_programs.exams
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:single,multi'],
            'target_class_ids' => ['nullable', 'array'], 'target_class_ids.*' => ['integer'],
            'target_group_ids' => ['nullable', 'array'], 'target_group_ids.*' => ['integer'],
            'expiry_hours' => ['required', 'integer'],
            'max_exit_attempts' => ['nullable', 'integer', 'min:1', 'max:50'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.sp_subject_id' => ['required', 'integer'],
            'subjects.*.teacher_id' => ['nullable', 'integer'],
            'subjects.*.duration_minutes' => ['required', 'integer'],
            'subjects.*.total_questions' => ['required', 'integer'],
            'subjects.*.order_index' => ['nullable', 'integer', 'min:1', 'max:50'],
            'subjects.*.term' => ['nullable', 'string'],
            'subjects.*.weeks' => ['nullable', 'array'], 'subjects.*.weeks.*' => ['integer'],
            'subjects.*.question_grade_levels' => ['nullable', 'array'], 'subjects.*.question_grade_levels.*' => ['integer', 'min:1', 'max:12'],
            'subjects.*.shares' => ['nullable', 'array'], 'subjects.*.shares.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'subjects.*.easy_pct' => ['nullable', 'integer'], 'subjects.*.medium_pct' => ['nullable', 'integer'], 'subjects.*.hard_pct' => ['nullable', 'integer'],
            'subjects.*.range_start' => ['nullable', 'integer', 'min:1'], 'subjects.*.range_end' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => __('special_programs/exams.title'), 'type' => __('special_programs/exams.type'),
            'expiry_hours' => __('special_programs/exams.expiry_hours'), 'subjects' => __('special_programs/exams.subjects')];
    }
}
