<?php

namespace App\Modules\StudentImport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();

        return $u && ($u->isSuperAdmin() || $u->isSchoolAdmin());
    }

    public function rules(): array
    {
        $rules = [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480'],
        ];

        // Super-admin must pick a school; school-admin is locked to their own.
        $user = $this->user();
        if ($user && $user->isSuperAdmin()) {
            $rules['school_id'] = ['required', 'integer', 'exists:schools,id'];
        } else {
            $rules['school_id'] = ['nullable', 'integer', 'exists:schools,id'];
        }

        return $rules;
    }
}
