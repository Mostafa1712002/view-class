<?php

namespace App\Modules\Communications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canDo('parents_contact.manage');
    }

    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:40'],
            'complaint_date' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'action_required' => ['nullable', 'string', 'max:5000'],
            'actions_taken' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'integer'],
            'status' => ['required', 'in:new,in_progress,awaiting_parent,resolved,closed'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,docx,xlsx', 'max:5120'],
        ];
    }
}
