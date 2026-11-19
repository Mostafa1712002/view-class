<?php

namespace App\Modules\SpecialPrograms\Repositories\Contracts;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpExam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface ExamRepository
{
    /** Staff scope: school; teacher = own/teaching. */
    public function visibleTo(User $user): Builder;

    /** 404 outside scope. */
    public function findForUser(int $id, User $user): SpExam;

    /** Exams where the student is in the audience. */
    public function forStudent(User $student): Collection;

    /** Users query. */
    public function audienceQuery(SpExam $exam): Builder;

    public function isInAudience(SpExam $exam, User $student): bool;
}
