<?php

namespace App\Modules\Gamification\Policies;

use App\Models\User;

/**
 * Abilities are checked against PointTransaction::class (no per-row model).
 * Never rely on canDo('points.view') alone: it default-allows every user.
 */
class PointsPolicy
{
    private function staff(User $u): bool
    {
        return $u->isSuperAdmin() || ($u->isSchoolAdmin() && $u->school_id !== null);
    }

    private function staffCan(User $u, string $permission): bool
    {
        return $u->isSuperAdmin() || ($this->staff($u) && $u->canDo($permission));
    }

    private function sameSchool(User $u, User $target): bool
    {
        return $u->isSuperAdmin() || (int) $u->school_id === (int) $target->school_id;
    }

    public function viewStaff(User $u): bool
    {
        return $this->staffCan($u, 'points.view');
    }

    public function adjust(User $u, ?User $target = null): bool
    {
        return $this->staffCan($u, 'points.adjust') && (! $target || $u->id !== $target->id);
    }

    public function manageRules(User $u): bool
    {
        return $this->staffCan($u, 'points.manage_rules');
    }

    public function editPlatformRules(User $u): bool
    {
        return $u->isSuperAdmin();
    }

    public function editRuleOverride(User $u, string $ruleKey = ''): bool
    {
        if (! $this->manageRules($u)) {
            return false;
        }

        return ! (str_starts_with($ruleKey, 'vice.') && $u->jobTitle?->slug === 'vice');
    }

    public function manageRewards(User $u): bool
    {
        return $this->staffCan($u, 'points.manage_rewards');
    }

    public function viewPerson(User $u, User $target): bool
    {
        return $this->viewStaff($u) && $this->sameSchool($u, $target);
    }

    public function viewChild(User $u, User $child): bool
    {
        return $u->isParent() && $u->children()->where('users.id', $child->id)->exists();
    }
}
