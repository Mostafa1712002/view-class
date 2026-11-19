<?php

namespace App\Modules\RawaniHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterBuyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s\-()]/', '', (string) $this->input('phone'));
        $this->merge(['phone' => $phone, 'email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'gender' => ['required', 'in:male,female'],
            'national_id' => ['required', 'regex:/^\d{10}$/'],
            'phone' => ['required', 'regex:/^(\+?966|0)?5\d{8}$/'], // Saudi mobile: 05XXXXXXXX, 9665XXXXXXXX, +9665XXXXXXXX
            'email' => ['required', 'email:rfc', 'max:191'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /** Phone in the 05XXXXXXXX form. */
    public function normalizedPhone(): string
    {
        return '0'.substr($this->validated('phone'), -9);
    }
}
