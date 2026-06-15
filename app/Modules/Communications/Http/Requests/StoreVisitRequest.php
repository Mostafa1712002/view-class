<?php

namespace App\Modules\Communications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canDo('parents_contact.manage');
    }

    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'integer'],
            'visit_date' => ['required', 'date'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
            'met_staff_id' => ['nullable', 'integer'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'next_action' => ['nullable', 'string', 'max:5000'],
            'followup_date' => ['nullable', 'date'],
            'status' => ['required', 'in:open,done,followup'],
        ];
    }
}
