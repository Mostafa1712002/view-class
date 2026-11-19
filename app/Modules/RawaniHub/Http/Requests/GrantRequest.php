<?php

namespace App\Modules\RawaniHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $new = $this->input('mode') === 'new';

        return [
            'package_id' => ['required', 'integer'],
            'mode' => ['required', 'in:existing,new'],
            'national_ids' => ['nullable', 'string', 'max:2000'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => ['integer'],
            'starts_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'name' => [$new ? 'required' : 'nullable', 'string', 'max:191'],
            'gender' => [$new ? 'required' : 'nullable', 'in:male,female'],
            'national_id' => [$new ? 'required' : 'nullable', 'string', 'max:20'],
            'stage' => [$new ? 'required' : 'nullable', 'in:primary,intermediate,secondary'],
            'grade' => [$new ? 'required' : 'nullable', 'integer', 'min:1', 'max:12'],
            'parent_name' => ['nullable', 'string', 'max:191'],
            'parent_national_id' => ['nullable', 'string', 'max:20'],
        ];
    }
}
