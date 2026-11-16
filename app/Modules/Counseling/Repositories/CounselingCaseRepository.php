<?php

namespace App\Modules\Counseling\Repositories;

use App\Models\User;
use App\Modules\Counseling\Models\CounselingCase;
use App\Modules\Counseling\Policies\CounselingCasePolicy;
use Illuminate\Database\Eloquent\Builder;

class CounselingCaseRepository
{
    /** All lists, searches and reports start here; unknown actors see nothing. */
    public function visibleTo(User $user): Builder
    {
        $query = CounselingCase::query();
        if (! (new CounselingCasePolicy)->viewAny($user)) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isSuperAdmin()) {
            return $query;
        }
        $query->where('school_id', $user->school_id);
        if (! $user->isSchoolAdmin()) {
            $query->where('counselor_id', $user->id);
        }

        return $query;
    }
}
