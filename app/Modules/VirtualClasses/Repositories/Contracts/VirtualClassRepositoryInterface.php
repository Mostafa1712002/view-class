<?php

namespace App\Modules\VirtualClasses\Repositories\Contracts;

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
    public function forStaff(int $userId, int $schoolId, bool $roleIsAdmin, string $tab = 'all', int $perPage = 20): LengthAwarePaginator;

    /**
     * Paginated list for students (upcoming + live classes for the school).
     */
    public function forStudent(int $userId, int $schoolId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a single virtual class, school-scoped.
     */
    public function find(int $id, int $schoolId): ?VirtualClass;

    public function create(array $data): VirtualClass;

    public function update(int $id, array $data): VirtualClass;

    public function updateStatus(int $id, string $status): VirtualClass;

    public function delete(int $id): void;

    /**
     * Record (or refresh) a participant's entry into a virtual class.
     */
    public function recordEntry(int $virtualClassId, int $studentId, int $schoolId): VirtualClassAttendee;

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
