<?php

namespace App\Modules\SpecialPrograms\Actions\Exams;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpExam;

/** Soft delete only: attempts stay, the exam leaves lists and reports. Creator or school-admin. */
final class DeleteProgramExam
{
    public function execute(SpExam $exam, User $user): void
    {
        abort_unless($user->isSuperAdmin() || $user->isSchoolAdmin() || (int) $exam->created_by === (int) $user->id, 403);
        $exam->delete();
    }
}
