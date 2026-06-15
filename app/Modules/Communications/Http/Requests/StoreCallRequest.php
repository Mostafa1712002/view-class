<?php

namespace App\Modules\Communications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canDo('parents_contact.manage');
    }

    public function rules(): array
    {
        return [
            'call_date' => ['required', 'date'],
            'call_time' => ['nullable', 'date_format:H:i'],
            'call_type' => ['required', 'in:incoming,outgoing'],
            'purpose' => ['required', 'string', 'max:255'],
            'outcome' => ['nullable', 'string', 'max:5000'],
            'answered' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'followup_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer'],
            'status' => ['required', 'in:scheduled,done,missed'],
        ];
    }
}
