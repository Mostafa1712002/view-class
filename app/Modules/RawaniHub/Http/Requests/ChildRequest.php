<?php

namespace App\Modules\RawaniHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'gender' => ['required', 'in:male,female'],
            'national_id' => ['required', 'regex:/^\d{10}$/'],
            'stage' => ['required', 'in:primary,intermediate,secondary'],
            'grade' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
