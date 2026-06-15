<?php

namespace App\Modules\Communications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canDo('parents_contact.manage');
    }

    public function rules(): array
    {
        $u = auth()->user();
        $schoolId = ($u && method_exists($u, 'isSuperAdmin') && $u->isSuperAdmin()) ? null : ($u->school_id ?? null);
        // Scope user FKs to the caller's school (super-admin = any) — no cross-tenant refs.
        $userInSchool = \Illuminate\Validation\Rule::exists('users', 'id')->where(
            fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q
        );

        return [
            'call_date' => ['required', 'date'],
            'call_time' => ['nullable', 'date_format:H:i'],
            'call_type' => ['required', 'in:incoming,outgoing'],
            'purpose' => ['required', 'string', 'max:255'],
            'outcome' => ['nullable', 'string', 'max:5000'],
            'answered' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'followup_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', $userInSchool],
            'status' => ['required', 'in:scheduled,done,missed'],
        ];
    }
}
