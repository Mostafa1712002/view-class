<?php

namespace App\Modules\SpecialPrograms\Actions\Enrollment;

use App\Modules\SpecialPrograms\Models\SpGroup;
use Illuminate\Validation\ValidationException;

final class SaveGroup
{
    /** @param array{name:string,teacher_id?:?int,target_level?:?string,notes?:?string} $data */
    public function execute(int $schoolId, string $program, array $data, int $userId, ?SpGroup $group = null): SpGroup
    {
        $teacherId = $data['teacher_id'] ?? null;
        if ($teacherId && ! AssignTeacher::isSchoolTeacher($schoolId, (int) $teacherId)) {
            throw ValidationException::withMessages(['teacher_id' => __('special_programs/groups.teacher_not_in_school')]);
        }
        $values = [
            'name' => $data['name'], 'teacher_id' => $teacherId ?: null,
            'target_level' => $data['target_level'] ?? null, 'notes' => $data['notes'] ?? null,
        ];

        return $group
            ? tap($group)->update($values)
            : SpGroup::create($values + ['school_id' => $schoolId, 'program' => $program, 'created_by' => $userId]);
    }

    /** Soft delete; members stay enrolled, just ungrouped. */
    public function delete(SpGroup $group): void
    {
        $group->enrollments()->update(['group_id' => null]);
        $group->delete();
    }
}
