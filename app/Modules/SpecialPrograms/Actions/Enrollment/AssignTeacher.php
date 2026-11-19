<?php

namespace App\Modules\SpecialPrograms\Actions\Enrollment;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpSubject;
use App\Modules\SpecialPrograms\Models\SpTeacher;
use Illuminate\Validation\ValidationException;

final class AssignTeacher
{
    public static function isSchoolTeacher(int $schoolId, int $userId): bool
    {
        return User::where('id', $userId)->where('school_id', $schoolId)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->exists();
    }

    /** $subjectId null = every subject of the program. «All» and single-subject rows never coexist for one teacher. */
    public function execute(int $schoolId, string $program, int $teacherId, ?int $subjectId, int $userId): SpTeacher
    {
        if (! self::isSchoolTeacher($schoolId, $teacherId)) {
            throw ValidationException::withMessages(['teacher_id' => __('special_programs/groups.teacher_not_in_school')]);
        }
        if ($subjectId !== null && ! SpSubject::where('id', $subjectId)->where('program', $program)->exists()) {
            throw ValidationException::withMessages(['sp_subject_id' => __('special_programs/groups.subject_not_in_program')]);
        }
        $mine = SpTeacher::withTrashed()->where('school_id', $schoolId)->where('teacher_id', $teacherId)->where('program', $program);

        if ($subjectId === null) {
            (clone $mine)->whereNotNull('sp_subject_id')->get()->each->forceDelete();
        } elseif ((clone $mine)->whereNull('sp_subject_id')->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages(['sp_subject_id' => __('special_programs/groups.already_all_subjects')]);
        }

        $row = (clone $mine)->where('sp_subject_id', $subjectId)->first();
        if ($row) {
            $row->trashed() && $row->restore();

            return $row;
        }

        return SpTeacher::create(['school_id' => $schoolId, 'program' => $program, 'teacher_id' => $teacherId,
            'sp_subject_id' => $subjectId, 'created_by' => $userId]);
    }
}
