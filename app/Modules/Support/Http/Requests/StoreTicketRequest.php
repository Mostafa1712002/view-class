<?php

namespace App\Modules\Support\Http\Requests;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user = auth()->user();

        // Only a parent may link a ticket to a student, and only to their own
        // child (parent_student pivot). Everyone else is rejected.
        $relatedStudentRule = ($user && $user->isParent())
            ? ['nullable', 'integer', Rule::exists('parent_student', 'student_id')->where('parent_id', $user->id)]
            : ['nullable', 'prohibited'];

        return [
            'type'               => ['nullable', Rule::in(SupportTicket::TYPES)],
            'category'           => ['required', 'string', 'max:40'],
            'department'         => ['nullable', Rule::in(SupportTicket::DEPARTMENTS)],
            'subject'            => ['required', 'string', 'max:160'],
            'body'               => ['required', 'string'],
            'problem_url'        => ['nullable', 'url', 'starts_with:http://,https://', 'max:500'],
            'priority'           => ['nullable', 'in:low,normal,high,urgent'],
            'related_student_id' => $relatedStudentRule,
            'attachment'         => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip'],
        ];
    }
}
