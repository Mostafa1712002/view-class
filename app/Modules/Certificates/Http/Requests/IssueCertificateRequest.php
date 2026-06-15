<?php

namespace App\Modules\Certificates\Http\Requests;

use App\Models\Certificate;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Template-based issuing of certificates to one or many students (single + bulk).
 * Distinct from StoreCertificateRequest (the legacy single file-upload flow),
 * which is kept intact for backward compatibility.
 */
class IssueCertificateRequest extends FormRequest
{
    use HasSchoolScope;

    public function authorize(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin() || $this->activeSchoolId() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->activeSchoolId();

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Certificate::TYPES)],
            'template_id' => [
                'required',
                'integer',
                Rule::exists('certificate_templates', 'id')->where(function ($q) use ($schoolId) {
                    if ($schoolId) {
                        $q->where('school_id', $schoolId);
                    }
                }),
            ],
            'issue_date' => ['required', 'date'],
            // One or more recipients — single + bulk both come through here.
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($q) use ($schoolId) {
                    if ($schoolId) {
                        $q->where('school_id', $schoolId);
                    }
                }),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
