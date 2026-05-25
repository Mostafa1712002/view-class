<?php

namespace App\Modules\NoorImport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoorImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();
        return $u && ($u->isSuperAdmin() || $u->isSchoolAdmin());
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['required', 'string', 'in:students,students_academic,teachers,admins'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
        ];

        // Super-admin must pick a school explicitly; school-admin is locked
        // to their own and the field is optional.
        $user = $this->user();
        if ($user && $user->isSuperAdmin()) {
            $rules['school_id'] = ['required', 'integer', 'exists:schools,id'];
        } else {
            $rules['school_id'] = ['nullable', 'integer', 'exists:schools,id'];
        }

        return $rules;
    }
}
