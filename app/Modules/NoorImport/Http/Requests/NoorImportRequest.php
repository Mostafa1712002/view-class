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
        return [
            'type' => ['required', 'string', 'in:students,students_academic,teachers,admins'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480'],
        ];
    }
}
