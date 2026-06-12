<?php

namespace App\Modules\Evaluation\Http\Requests;

use App\Modules\Evaluation\Enums\OutcomeMethod;
use App\Modules\Evaluation\Enums\OutcomeSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Phase C (#205) — Validate the payload for creating a new EvaluationOutcome.
 */
class StoreEvaluationOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Role gate is on the route; no additional policy needed for Phase C.
    }

    public function rules(): array
    {
        $validMethods = array_column(OutcomeMethod::cases(), 'value');
        $validSources = array_column(OutcomeSource::cases(), 'value');

        return [
            // Core metadata
            'test_name'              => ['required', 'string', 'max:255'],
            'test_type'              => ['nullable', 'string', 'max:80'],
            'test_date'              => ['nullable', 'date'],
            'grade_level'            => ['nullable', 'string', 'max:80'],
            'class_label'            => ['nullable', 'string', 'max:80'],
            'teacher_id'             => ['nullable', 'integer', 'min:1'],
            'subject_id'             => ['nullable', 'integer', 'min:1'],
            'educational_company_id' => ['nullable', 'integer', 'min:1'],

            // Source + method
            'source'  => ['nullable', Rule::in($validSources)],
            'method'  => ['nullable', Rule::in($validMethods)],

            // Students array — minimum 1 entry
            'students'                  => ['required', 'array', 'min:1'],
            'students.*.student_id'     => ['required', 'integer', 'min:1'],
            // Score is only required for PRESENT students — an absent student has
            // no score (the calculator treats absent as 0 / excludes them).
            'students.*.score'          => ['nullable', 'required_if:students.*.status,present', 'numeric', 'min:0', 'max:100'],
            'students.*.status'         => ['required', Rule::in(['present', 'absent'])],
        ];
    }

    public function messages(): array
    {
        return [
            'test_name.required'                => __('evaluation_outcomes.validation.test_name_required'),
            'students.required'                 => __('evaluation_outcomes.validation.students_required'),
            'students.min'                      => __('evaluation_outcomes.validation.students_min'),
            'students.*.student_id.required'    => __('evaluation_outcomes.validation.student_id_required'),
            'students.*.score.numeric'          => __('evaluation_outcomes.validation.score_numeric'),
            'students.*.score.min'              => __('evaluation_outcomes.validation.score_min'),
            'students.*.score.max'              => __('evaluation_outcomes.validation.score_max'),
            'students.*.status.in'              => __('evaluation_outcomes.validation.status_invalid'),
        ];
    }
}
