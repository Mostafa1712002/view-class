<?php

namespace App\Modules\Counseling\Policies;

use App\Models\User;
use App\Modules\Counseling\Models\CounselingCase;

class CounselingCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || ($user->school_id !== null &&
            ($user->isSchoolAdmin() || $user->jobTitle?->slug === 'counselor'));
    }

    public function view(User $user, CounselingCase $case): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->viewAny($user) && (int) $user->school_id === (int) $case->school_id &&
            ($user->isSchoolAdmin() || (int) $case->counselor_id === (int) $user->id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, CounselingCase $case): bool
    {
        return $this->view($user, $case);
    }

    public function delete(User $user, CounselingCase $case): bool
    {
        return $this->view($user, $case) && ($user->isSuperAdmin() || $user->isSchoolAdmin());
    }
}
