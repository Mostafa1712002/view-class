<?php

namespace App\Modules\SpecialPrograms\Http\Requests;

use App\Modules\SpecialPrograms\Support\PerformanceLevels;
use App\Modules\SpecialPrograms\Support\Programs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Used by both the school (program in the URL) and the global (program in the body) save. */
final class LevelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware + controller decide
    }

    public function program(): string
    {
        return (string) ($this->route('program') ?? $this->input('program'));
    }

    public function rules(): array
    {
        return [
            'program' => ['sometimes', Rule::in(Programs::keys())],
            'bands' => ['required', 'array'],
            'bands.*.min' => ['required', 'integer', 'between:0,100'],
            'bands.*.max' => ['required', 'integer', 'between:0,100'],
            'grade_levels' => ['sometimes', 'array'],
            'grade_levels.*' => ['integer', 'between:1,12'],
            'weeks_count' => ['nullable', 'integer', 'between:1,60'],
            'allow_schools' => ['sometimes', 'array'],
            'allow_schools.*' => ['integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->has('bands.*') || $v->errors()->has('bands')) {
                return;
            }
            $rows = [];
            foreach ((array) $this->input('bands') as $key => $b) {
                $rows[] = ['key' => $key, 'min' => $b['min'] ?? null, 'max' => $b['max'] ?? null];
            }
            if ($msg = PerformanceLevels::validate($rows)) {
                $v->errors()->add('bands', $msg);
            }
        });
    }
}
