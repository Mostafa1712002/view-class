<?php

namespace App\Modules\SpecialPrograms\Repositories\Contracts;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpExam;
use Illuminate\Support\Collection;

interface AttemptRepository
{
    /** 404 otherwise; finalizes if overdue. */
    public function findForStudent(int $attemptId, User $student): SpAttempt;

    public function forExamAndStudent(int $examId, int $studentId): Collection;

    /** Per subject × audience student status. */
    public function monitor(SpExam $exam): Collection;
}
