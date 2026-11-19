<?php

namespace App\Modules\SpecialPrograms\Actions\Picking;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpExamSubject;

interface QuestionPicker
{
    /** Ordered sp_question ids for the student; passage members contiguous. */
    public function pick(SpExamSubject $subject, User $student): array;
}
