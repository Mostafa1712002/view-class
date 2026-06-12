<?php

namespace App\Modules\VirtualClasses\Repositories\Contracts;

use App\Models\VirtualClass;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VirtualClassRepositoryInterface
{
    /**
     * Paginated list for staff.
     * Admin sees all in school; teacher sees only their own sessions.
     */
    public function forStaff(int $userId, int $schoolId, bool $roleIsAdmin, int $perPage = 20): LengthAwarePaginator;

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
}
