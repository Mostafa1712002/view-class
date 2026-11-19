<?php

namespace App\Modules\SpecialPrograms\Actions\Enrollment;

use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;

final class RemoveEnrollment
{
    public function __construct(private EnrollmentRepository $enrollments) {}

    public function execute(int $schoolId, string $program, int $enrollmentId): void
    {
        $row = SpEnrollment::where(['id' => $enrollmentId, 'school_id' => $schoolId, 'program' => $program])->firstOrFail();
        $this->enrollments->remove($schoolId, $program, (int) $row->student_id);
    }
}
