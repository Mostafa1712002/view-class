<?php

namespace App\Modules\Support\Http\Requests;

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
            'category'           => ['required', 'string', 'max:40'],
            'subject'            => ['required', 'string', 'max:160'],
            'body'               => ['required', 'string'],
            'priority'           => ['nullable', 'in:low,normal,high,urgent'],
            'related_student_id' => $relatedStudentRule,
        ];
    }
}
