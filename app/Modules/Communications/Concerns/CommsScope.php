<?php

namespace App\Modules\Communications\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared data-scope helper for all Sprint-9 communication modules.
 *
 * Centralises the multi-tenant + role-narrowing rule documented in
 * .kiro/specs/trello-sprint9-comms-foundation/design.md so every comms
 * repository reuses ONE implementation instead of re-deriving it per card.
 *
 * Usage (inside a repository/action):
 *   $query = $this->scopeToUser(Announcement::query(), auth()->user());
 *
 * Later cards extend this trait with module-specific narrowing
 * (e.g. teacher → own classes for virtual classes) on top of the school_id
 * baseline applied here.
 */
trait CommsScope
{
    /**
     * Apply the school_id baseline scope. Super-admins see all schools; every
     * other role is constrained to their own school.
     *
     * @param  string  $column  the school_id column on the queried table
     */
    protected function scopeToSchool(Builder $query, ?User $user, string $column = 'school_id'): Builder
    {
        if ($user === null) {
            // No actor → return an impossible constraint (fail closed).
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query; // company/group scopes are layered by the calling card when granted
        }

        return $query->where($column, $user->school_id);
    }

    /**
     * Resolve the set of class IDs a user is scoped to, for class-bound comms
     * (announcements targeted at classes, virtual classes, discussion rooms…).
     *
     *   teacher → classes they teach (schedule periods)
     *   student → classes they're enrolled in
     *   parent  → their children's enrolled classes
     *   admins  → null (no class restriction; school_id already applied)
     *
     * @return array<int>|null  null = no class restriction
     */
    protected function scopedClassIds(?User $user): ?array
    {
        if ($user === null || $user->isSuperAdmin() || $user->isSchoolAdmin()) {
            return null;
        }

        if ($user->isStudent()) {
            return $user->enrolledClassIds();
        }

        if ($user->isParent()) {
            return $user->children
                ->flatMap(fn (User $child) => $child->enrolledClassIds())
                ->unique()
                ->values()
                ->all();
        }

        if ($user->isTeacher()) {
            // TODO (later comms cards): resolve a teacher's class IDs.
            // `schedule_periods` has NO class_room_id column — the class link is
            // indirect (schedule_periods.schedule_id → schedules → class). The
            // correct join depends on the schedules schema and the card's data
            // model, so it is intentionally left to the module that needs it
            // rather than shipping a wrong column reference here. Until then the
            // teacher is scoped by school_id only (returned null = no class
            // restriction). Do NOT guess a column name.
            return null;
        }

        return null;
    }
}
