<?php

namespace App\Modules\SchoolCalendar\Repositories\Contracts;

use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Support\Collection;

interface SchoolEventRepository
{
    /**
     * Return events for a school within a date range.
     * Optionally filtered to events visible to a given audience key.
     */
    public function forRange(?int $schoolId, string $from, string $to, ?string $audienceKey = null): Collection;

    /**
     * Return all events for a school (latest first), no date filter.
     */
    public function all(?int $schoolId): Collection;

    /**
     * Find an event by id (no school scope — caller must gate).
     */
    public function findById(int $id): ?SchoolEvent;

    /**
     * Create a new event.
     */
    public function create(array $data): SchoolEvent;

    /**
     * Update an existing event.
     */
    public function update(int $id, array $data): SchoolEvent;

    /**
     * Soft-delete an event.
     */
    public function delete(int $id): bool;

    /**
     * Replace the explicit per-event targets (kind=user / kind=role).
     *
     * @param int[] $userTargetIds
     * @param int[] $roleTargetIds
     */
    public function syncTargets(SchoolEvent $event, array $userTargetIds, array $roleTargetIds = []): void;

    /**
     * Resolve the distinct set of users an event targets (for notifications).
     */
    public function resolveTargetedUsers(SchoolEvent $event): Collection;
}
