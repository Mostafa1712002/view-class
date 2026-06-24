<?php

namespace App\Modules\Announcements\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is permission-gated; controller re-checks canDo for write actions.
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'body'            => ['nullable', 'string'],
            'type'            => ['required', Rule::in(['normal', 'important', 'popup'])],
            'target_type'     => ['required', Rule::in([
                'all', 'students', 'teachers', 'parents', 'admins',
                'specific_users', 'specific_roles', 'job_titles',
            ])],

            'grade_levels'    => ['nullable', 'array'],
            'grade_levels.*'  => ['integer'],
            'class_ids'       => ['nullable', 'array'],
            'class_ids.*'     => ['integer'],
            'subject_ids'     => ['nullable', 'array'],
            'subject_ids.*'   => ['integer'],

            'user_target_ids'   => ['nullable', 'array'],
            'user_target_ids.*' => ['integer'],
            'role_target_ids'   => ['nullable', 'array'],
            'role_target_ids.*' => ['integer'],
            'job_title_ids'     => ['nullable', 'array'],
            'job_title_ids.*'   => ['integer'],

            'starts_at'       => ['nullable', 'date'],
            'ends_at'         => ['nullable', 'date', 'after_or_equal:starts_at'],

            'show_on_login'    => ['nullable', 'boolean'],
            'require_read_ack' => ['nullable', 'boolean'],
            'notify_internal'  => ['nullable', 'boolean'],
            'notify_sms'       => ['nullable', 'boolean'],
            'notify_whatsapp'  => ['nullable', 'boolean'],

            // 'draft' or 'publish' button
            'action'          => ['required', Rule::in(['draft', 'publish'])],

            'school_id'       => ['nullable', 'integer'], // only used by super-admin
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('target_type') === 'specific_users'
                && empty($this->input('user_target_ids'))) {
                $validator->errors()->add('user_target_ids', 'يجب اختيار مستخدم واحد على الأقل.');
            }
            if ($this->input('target_type') === 'specific_roles'
                && empty($this->input('role_target_ids'))) {
                $validator->errors()->add('role_target_ids', 'يجب اختيار دور واحد على الأقل.');
            }
            if ($this->input('target_type') === 'job_titles'
                && empty($this->input('job_title_ids'))) {
                $validator->errors()->add('job_title_ids', 'يجب اختيار مسمى وظيفي واحد على الأقل.');
            }
        });
    }
}
