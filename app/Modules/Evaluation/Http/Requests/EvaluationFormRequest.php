<?php

namespace App\Modules\Evaluation\Http\Requests;

use App\Modules\Evaluation\Enums\FormStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Enums\UsageDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EvaluationFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level role middleware guards access
    }

    public function rules(): array
    {
        $types   = array_keys(FormType::options());
        $domains = array_keys(UsageDomain::options());

        return [
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'type'           => ['required', Rule::in($types)],
            'usage_domain'   => ['required', Rule::in($domains)],
            // Phase E (#202) — shared evaluation mode flag
            'shared_mode'    => ['nullable', 'boolean'],
            'levels_count'   => ['nullable', 'integer', 'min:2', 'max:10'],
            'level_labels'   => ['nullable', 'array'],
            'level_labels.*' => ['nullable', 'string', 'max:100'],
            'start_date'     => ['nullable', 'date'],
            'close_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'settings'       => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $type = $this->input('type');

            // Rubric & Rating Scale need a valid levels configuration with non-empty labels.
            if (in_array($type, [FormType::Rubric->value, FormType::RatingScale->value], true)) {
                $count  = (int) $this->input('levels_count', 0);
                $labels = array_filter((array) $this->input('level_labels', []), fn ($l) => trim((string) $l) !== '');
                if ($count < 2) {
                    $v->errors()->add('levels_count', __('evaluation.form.errors.levels_min'));
                } elseif (count($labels) < $count) {
                    $v->errors()->add('level_labels', __('evaluation.form.errors.levels_empty'));
                }
            }

            // Class-visit-only must align with the class_visit domain.
            if ($this->boolean('settings.class_visit_only')
                && $this->input('usage_domain') !== UsageDomain::ClassVisit->value) {
                $v->errors()->add('usage_domain', __('evaluation.form.errors.class_visit_mismatch'));
            }
        });
    }
}
