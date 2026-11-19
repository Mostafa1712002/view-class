<?php

namespace App\Modules\SpecialPrograms\Actions\Enrollment;

use App\Models\ClassRoom;
use App\Modules\SpecialPrograms\Models\SpGroup;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use App\Modules\SpecialPrograms\Support\ProgramAudience;
use Illuminate\Validation\ValidationException;

final class EnrollStudents
{
    public function __construct(private EnrollmentRepository $enrollments) {}

    /**
     * @param int[] $studentIds
     * @param ?int $classId with $all = true, enrols every student of that class (resolved here, not posted)
     * @return int number newly enrolled or restored
     */
    public function execute(int $schoolId, string $program, array $studentIds, bool $all, ?int $classId, ?int $groupId, int $byUserId): int
    {
        if ($all) {
            abort_unless($classId && ClassRoom::whereKey($classId)->whereHas('section', fn ($q) => $q->where('school_id', $schoolId))->exists(), 404);
            $studentIds = ProgramAudience::studentsQuery($schoolId, [$classId])->pluck('users.id')->all();
        }
        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        if ($studentIds === []) {
            throw ValidationException::withMessages(['student_ids' => __('special_programs/staff.students_required')]);
        }
        // Foreign-school (or non-student) ids are rejected, never silently dropped.
        abort_unless(ProgramAudience::studentsQuery($schoolId)->whereIn('users.id', $studentIds)->count() === count($studentIds), 422);
        abort_if($groupId && ! SpGroup::where(['id' => $groupId, 'school_id' => $schoolId, 'program' => $program])->exists(), 422);

        return $this->enrollments->enroll($schoolId, $program, $studentIds, $groupId, $byUserId);
    }
}
