<?php

namespace App\Modules\Communications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
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
            'student_id' => ['nullable', 'integer', $userInSchool],
            'visit_date' => ['required', 'date'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
            'met_staff_id' => ['nullable', 'integer', $userInSchool],
            'summary' => ['nullable', 'string', 'max:5000'],
            'next_action' => ['nullable', 'string', 'max:5000'],
            'followup_date' => ['nullable', 'date'],
            'status' => ['required', 'in:open,done,followup'],
        ];
    }
}
