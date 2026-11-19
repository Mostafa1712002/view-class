<?php

namespace App\Modules\SpecialPrograms\Http\Requests;

use App\Modules\SpecialPrograms\Support\PerformanceLevels;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware: role + permission:special_programs.manage
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'integer'],
            'target_level' => ['nullable', Rule::in(array_keys(PerformanceLevels::LEVELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('special_programs/groups.name'), 'teacher_id' => __('special_programs/groups.teacher'),
            'target_level' => __('special_programs/groups.target_level'), 'notes' => __('special_programs/groups.notes'),
        ];
    }
}
