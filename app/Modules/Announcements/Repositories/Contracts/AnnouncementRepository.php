<?php

namespace App\Modules\Announcements\Repositories\Contracts;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AnnouncementRepository
{
    /**
     * Paginated, school-scoped list with optional filters.
     *
     * @param int|null $schoolId  null = super-admin (all schools)
     * @param array    $filters   search, status, type, target_type
     */
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a single announcement honouring school scope. Returns null when
     * out of scope (so the controller can 403/404).
     */
    public function find(int $id, ?int $schoolId): ?Announcement;

    /** Persist a new announcement + its targets (inside a transaction). */
    public function create(array $data, array $userTargetIds = [], array $roleTargetIds = [], array $jobTitleIds = []): Announcement;

    /** Update an announcement + replace its targets. */
    public function update(Announcement $announcement, array $data, array $userTargetIds = [], array $roleTargetIds = [], array $jobTitleIds = []): Announcement;

    /** Set base status (draft|published|stopped) and stamp published_at on first publish. */
    public function setStatus(Announcement $announcement, string $status): Announcement;

    /** Soft delete. */
    public function delete(Announcement $announcement): void;

    /** Duplicate an announcement as a fresh draft (copies targets). */
    public function duplicate(Announcement $announcement): Announcement;

    // ── Targeting resolver (single source of truth) ──────────────────────────

    /**
     * Resolve the full set of users this announcement targets.
     *
     * @return Collection<int,User>
     */
    public function resolveTargetedUsers(Announcement $announcement): Collection;

    /** Whether a specific user is among the announcement's targets. */
    public function isUserTargeted(Announcement $announcement, User $user): bool;

    // ── End-user display ─────────────────────────────────────────────────────

    /**
     * Live announcements (published + within window) that target $user.
     *
     * @param bool $popupOnly  only type=popup with show_on_login
     * @return Collection<int,Announcement>
     */
    public function liveForUser(User $user, bool $popupOnly = false): Collection;

    public function findLiveForUser(int $id, User $user): ?Announcement;

    // ── Read log ─────────────────────────────────────────────────────────────

    /** Record (or refresh) that a user viewed an announcement. */
    public function recordView(Announcement $announcement, User $user, ?string $ip = null, ?string $device = null): void;

    /** Mark that a user confirmed reading an announcement. */
    public function confirmRead(Announcement $announcement, User $user, ?string $ip = null, ?string $device = null): void;

    /**
     * Read-log roster: every targeted user with their read state.
     *
     * @return array{read: Collection, unread: Collection}
     */
    public function readLog(Announcement $announcement): array;
}
