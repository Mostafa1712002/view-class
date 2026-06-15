<?php

namespace App\Modules\Mail\Http\Requests;

use App\Models\InternalMail;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMailRequest extends FormRequest
{
    use HasSchoolScope;

    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        // Authorization runs before validation, so the permission gate is
        // enforced regardless of payload validity: drafting needs
        // mailbox.draft, sending needs mailbox.send. (The controller also
        // gates as defence-in-depth.)
        $permission = $this->input('action') === 'draft' ? 'mailbox.draft' : 'mailbox.send';

        return auth()->user()->canDo($permission);
    }

    public function rules(): array
    {
        $isDraft  = $this->input('action') === 'draft';
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();

        // Recipients (and the parent's related student) must belong to the sender's
        // own school — never an arbitrary cross-tenant user id.
        $inSchool = fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q;

        // related_student_id: only a parent may set it, and only to one of their
        // own children (via the parent_student pivot). Anyone else is rejected.
        $relatedStudentRule = ($user && $user->isParent())
            ? ['nullable', 'integer', Rule::exists('parent_student', 'student_id')->where('parent_id', $user->id)]
            : ['nullable', 'prohibited'];

        return [
            'subject'            => ['required', 'string', 'max:255'],
            'importance'         => ['required', Rule::in(InternalMail::IMPORTANCES)],
            'body'               => ['required', 'string'],
            'to'                 => $isDraft ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'to.*'               => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $inSchool($q))],
            'related_student_id' => $relatedStudentRule,
            // Vetted attachment types only — no SVG/HTML/JS/XML (stored-XSS vectors).
            'attachment'         => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,gif', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,gif'],
            'action'             => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required'    => 'الموضوع مطلوب.',
            'subject.max'         => 'الموضوع يجب ألا يتجاوز 255 حرفاً.',
            'importance.required' => 'الأهمية مطلوبة.',
            'importance.in'       => 'قيمة الأهمية غير صحيحة.',
            'body.required'       => 'نص الرسالة مطلوب.',
            'to.required'         => 'يجب اختيار مستلم واحد على الأقل.',
            'to.min'              => 'يجب اختيار مستلم واحد على الأقل.',
            'to.*.exists'         => 'أحد المستلمين المختارين غير موجود.',
            'attachment.file'     => 'الملف المرفق غير صالح.',
            'attachment.max'      => 'حجم المرفق يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }
}
