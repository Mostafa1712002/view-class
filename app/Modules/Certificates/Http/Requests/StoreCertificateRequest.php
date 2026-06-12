<?php

namespace App\Modules\Certificates\Http\Requests;

use App\Models\Certificate;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateRequest extends FormRequest
{
    use HasSchoolScope;

    public function authorize(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // A non super-admin must operate within a concrete school scope. This
        // forbids the null-scope cross-tenant bypass: without it, a user whose
        // active school resolves to null would have the school_id constraint on
        // recipient_user_id/issued_by silently dropped. Super-admin is global.
        return $user->isSuperAdmin() || $this->activeSchoolId() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->activeSchoolId();

        // issued_by is intentionally NOT accepted from the client — the issuer
        // is the authenticated admin, set server-side in the controller.
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
            'issue_date' => ['required', 'date'],
            'status' => ['required', Rule::in(Certificate::STATUSES)],
            'note' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ];
    }
}
