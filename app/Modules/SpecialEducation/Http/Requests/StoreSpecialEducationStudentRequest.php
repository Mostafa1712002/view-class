<?php

namespace App\Modules\SpecialEducation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpecialEducationStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $schoolId = auth()->user()?->school_id ?? 0;

        return [
            'student_id'          => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('school_id', $schoolId)->where(fn ($q) => $q->whereIn('id', self::roleUserIds('student'))),
            ],
            'category'            => ['required', 'string', 'in:learning_disability,gifted,speech,physical,behavioral,visual,hearing,other'],
            'diagnosis'           => ['nullable', 'string'],
            'severity'            => ['nullable', 'string', 'in:mild,moderate,severe'],
            'assigned_specialist' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('school_id', $schoolId)->where(fn ($q) => $q->whereIn('id', self::roleUserIds('teacher'))),
            ],
            'status'              => ['required', 'string', 'in:active,inactive,graduated'],
            'notes'               => ['nullable', 'string'],
        ];
    }

    /** Users holding the given role slug (students for the student field, teachers for the specialist). */
    public static function roleUserIds(string $slug): \Illuminate\Database\Query\Builder
    {
        return \Illuminate\Support\Facades\DB::table('role_user')->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.slug', $slug)->select('role_user.user_id');
    }
}
