<?php

namespace App\Modules\RawaniHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer'],
            'national_id' => ['nullable', 'regex:/^\d{10}$/'],
            'gender' => ['nullable', 'in:male,female'],
            'stage' => ['nullable', 'in:primary,intermediate,secondary'],
            'grade' => ['nullable', 'integer', 'min:1', 'max:12'],
            'parent_national_id' => ['nullable', 'regex:/^\d{10}$/'],
        ];
    }
}
