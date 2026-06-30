<?php

namespace App\Modules\VirtualClasses\Repositories\Contracts;

use App\Models\User;
use App\Models\VirtualClass;
use App\Modules\VirtualClasses\Models\VirtualClassAttendee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface VirtualClassRepositoryInterface
{
    /**
     * Paginated list for staff, filtered by tab.
     * Admin sees all in school; teacher sees only their own sessions.
     *
     * @param string $tab one of: today | recorded | old | all
     */
    public function forStaff(int $userId, ?int $schoolId, bool $roleIsAdmin, string $tab = 'all', int $perPage = 20): LengthAwarePaginator;

    /**
     * Paginated list of upcoming/live sessions that target the given user
     * (by audience type, grade/class, or explicit user/role/job-title pick).
     */
    public function forStudent(User $user, ?int $schoolId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Whether a session is visible to the given user (drives the join gate).
     */
    public function isVisibleTo(VirtualClass $vc, User $user): bool;

    /**
     * Find a single virtual class, school-scoped.
     */
    public function find(int $id, ?int $schoolId): ?VirtualClass;

    /**
     * @param array{user:array<int>,role:array<int>,job_title:array<int>} $targets
     */
    public function create(array $data, array $targets = []): VirtualClass;

    /**
     * @param array{user:array<int>,role:array<int>,job_title:array<int>} $targets
     */
    public function update(int $id, array $data, ?array $targets = null): VirtualClass;

    public function updateStatus(int $id, string $status): VirtualClass;

    public function delete(int $id): void;

    /**
     * Record (or refresh) a participant's entry into a virtual class.
     */
    public function recordEntry(int $virtualClassId, int $studentId, ?int $schoolId): VirtualClassAttendee;

    /**
     * All attendee log rows for a virtual class (with student eager-loaded).
     *
     * @return Collection<int, VirtualClassAttendee>
     */
    public function attendeesFor(int $virtualClassId): Collection;

    /**
     * The enrolled-student ids that form the expected roster for a class.
     *
     * @return array<int>
     */
    public function rosterStudentIds(?int $classId): array;
}
