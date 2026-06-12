<?php

namespace App\Modules\Certificates\Http\Requests;

use App\Models\Certificate;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificateRequest extends FormRequest
{
    use HasSchoolScope;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $schoolId = $this->activeSchoolId();

        return [
            'type' => ['required', Rule::in(Certificate::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'recipient_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) use ($schoolId) {
                    if ($schoolId) {
                        $q->where('school_id', $schoolId);
                    }
                }),
            ],
            'issued_by' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) use ($schoolId) {
                    if ($schoolId) {
                        $q->where('school_id', $schoolId);
                    }
                }),
            ],
            'issue_date' => ['required', 'date'],
            'status' => ['required', Rule::in(Certificate::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ];
    }
}
