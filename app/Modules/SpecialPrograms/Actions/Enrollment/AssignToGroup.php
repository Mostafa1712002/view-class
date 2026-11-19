<?php

namespace App\Modules\SpecialPrograms\Actions\Enrollment;

use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Models\SpGroup;

/** One group per student per program: group_id lives on the enrolment, so assigning moves. */
final class AssignToGroup
{
    /** @param int[] $studentIds @return int students actually placed (non-enrolled ids are ignored) */
    public function execute(SpGroup $group, array $studentIds): int
    {
        return SpEnrollment::where('school_id', $group->school_id)->where('program', $group->program)
            ->whereIn('student_id', $studentIds)->update(['group_id' => $group->id]);
    }

    public function remove(SpGroup $group, int $studentId): void
    {
        SpEnrollment::where('group_id', $group->id)->where('student_id', $studentId)->update(['group_id' => null]);
    }
}
