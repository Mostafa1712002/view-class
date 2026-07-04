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

            // Design fields (signer, signature, logo, stamp, free-text body).
            'signer_name'    => ['nullable', 'string', 'max:255'],
            'signature_type' => ['nullable', Rule::in(['manual', 'file'])],
            'signature_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1536'],
            // data:image/png;base64,... produced by the canvas signature pad.
            'signature_data' => ['nullable', 'string'],
            'logo'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1536'],
            'stamp' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1536'],
            // Rich body — required only for the free-text 'general' certificate.
            'body_html' => ['nullable', 'string', 'max:20000', 'required_if:type,general'],
        ];
    }
}
