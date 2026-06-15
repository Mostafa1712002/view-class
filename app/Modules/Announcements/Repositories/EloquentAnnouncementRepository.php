<?php

namespace App\Modules\Announcements\Repositories;

use App\Models\Announcement;
use App\Models\AnnouncementTarget;
use App\Models\User;
use App\Modules\Announcements\Repositories\Contracts\AnnouncementRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentAnnouncementRepository implements AnnouncementRepository
{
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Announcement::query()
            ->with(['creator', 'school'])
            ->withCount('reads')
            ->latest('id');

        $this->scopeToSchool($query, $schoolId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        // Status filter on the DERIVED status.
        if (!empty($filters['status'])) {
            $this->filterByEffectiveStatus($query, $filters['status']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id, ?int $schoolId): ?Announcement
    {
        $query = Announcement::query()->with(['creator', 'school', 'targets']);
        $this->scopeToSchool($query, $schoolId);

        return $query->find($id);
    }

    public function create(array $data, array $userTargetIds = [], array $roleTargetIds = []): Announcement
    {
        return DB::transaction(function () use ($data, $userTargetIds, $roleTargetIds) {
            $announcement = Announcement::create($data);
            $this->syncTargets($announcement, $userTargetIds, $roleTargetIds);

            return $announcement->fresh(['targets']);
        });
    }

    public function update(Announcement $announcement, array $data, array $userTargetIds = [], array $roleTargetIds = []): Announcement
    {
        return DB::transaction(function () use ($announcement, $data, $userTargetIds, $roleTargetIds) {
            $announcement->update($data);
            $this->syncTargets($announcement, $userTargetIds, $roleTargetIds);

            return $announcement->fresh(['targets']);
        });
    }

    public function setStatus(Announcement $announcement, string $status): Announcement
    {
        $payload = ['status' => $status];
        if ($status === 'published' && $announcement->published_at === null) {
            $payload['published_at'] = now();
        }
        $announcement->update($payload);

        return $announcement->fresh();
    }

    public function delete(Announcement $announcement): void
    {
        $announcement->delete();
    }

    public function duplicate(Announcement $announcement): Announcement
    {
        return DB::transaction(function () use ($announcement) {
            $copy = $announcement->replicate([
                'status', 'published_at', 'created_at', 'updated_at', 'deleted_at',
            ]);
            $copy->status = 'draft';
            $copy->published_at = null;
            $copy->title = $announcement->title . ' - نسخة';
            $copy->created_by = auth()->id() ?? $announcement->created_by;
            $copy->save();

            foreach ($announcement->targets as $target) {
                AnnouncementTarget::create([
                    'announcement_id' => $copy->id,
                    'kind'            => $target->kind,
                    'target_id'       => $target->target_id,
                ]);
            }

            return $copy->fresh(['targets']);
        });
    }

    // ── Targeting resolver ───────────────────────────────────────────────────

    public function resolveTargetedUsers(Announcement $announcement): Collection
    {
        return $this->targetedUsersQuery($announcement)->get();
    }

    public function isUserTargeted(Announcement $announcement, User $user): bool
    {
        return $this->targetedUsersQuery($announcement)
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * The ONE query that defines who an announcement targets. Both the display
     * gate and the read-log roster derive from this so they cannot drift.
     */
    protected function targetedUsersQuery(Announcement $announcement): Builder
    {
        $query = User::query()->where('users.school_id', $announcement->school_id);

        switch ($announcement->target_type) {
            case 'all':
                // everyone in the school
                break;

            case 'students':
                $query->whereHas('roles', fn ($q) => $q->where('slug', 'student'));
                $this->applyStudentNarrowing($query, $announcement);
                break;

            case 'teachers':
                $query->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'));
                break;

            case 'parents':
                $query->whereHas('roles', fn ($q) => $q->where('slug', 'parent'));
                break;

            case 'admins':
                $query->whereHas('roles', fn ($q) => $q->whereIn('slug', ['school-admin', 'super-admin']));
                break;

            case 'specific_roles':
                $roleIds = $announcement->targets->where('kind', 'role')->pluck('target_id')->all();
                if (empty($roleIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds));
                }
                break;

            case 'specific_users':
                $userIds = $announcement->targets->where('kind', 'user')->pluck('target_id')->all();
                $query->whereIn('users.id', $userIds ?: [0]);
                break;
        }

        return $query;
    }

    /**
     * Narrow a student query by grade levels and/or class ids when configured.
     */
    protected function applyStudentNarrowing(Builder $query, Announcement $announcement): void
    {
        $gradeLevels = $announcement->grade_levels ?: [];
        $classIds    = $announcement->class_ids ?: [];

        if (empty($gradeLevels) && empty($classIds)) {
            return;
        }

        $query->where(function (Builder $q) use ($gradeLevels, $classIds) {
            if (!empty($classIds)) {
                // direct column OR class_student pivot (mirrors User::enrolledClassIds)
                $q->orWhereIn('users.class_room_id', $classIds)
                    ->orWhereHas('enrolledClasses', fn ($cq) => $cq->whereIn('classes.id', $classIds));
            }
            if (!empty($gradeLevels)) {
                $q->orWhereHas('enrolledClasses', fn ($cq) => $cq->whereIn('classes.grade_level', $gradeLevels))
                    ->orWhereExists(function ($sub) use ($gradeLevels) {
                        $sub->select(DB::raw(1))
                            ->from('classes')
                            ->whereColumn('classes.id', 'users.class_room_id')
                            ->whereIn('classes.grade_level', $gradeLevels);
                    });
            }
        });
    }

    // ── End-user display ─────────────────────────────────────────────────────

    public function liveForUser(User $user, bool $popupOnly = false): Collection
    {
        $now = now();

        $candidates = Announcement::query()
            ->with('targets')
            ->where('school_id', $user->school_id)
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->when($popupOnly, fn ($q) => $q->where('type', 'popup')->where('show_on_login', true))
            ->latest('published_at')
            ->get();

        return $candidates->filter(fn (Announcement $a) => $this->isUserTargeted($a, $user))->values();
    }

    public function findLiveForUser(int $id, User $user): ?Announcement
    {
        $announcement = Announcement::query()
            ->with('targets')
            ->where('school_id', $user->school_id)
            ->find($id);

        if (!$announcement || !$announcement->isLive()) {
            return null;
        }

        return $this->isUserTargeted($announcement, $user) ? $announcement : null;
    }

    // ── Read log ─────────────────────────────────────────────────────────────

    public function recordView(Announcement $announcement, User $user, ?string $ip = null, ?string $device = null): void
    {
        $read = $announcement->reads()->firstOrNew(['user_id' => $user->id]);
        if ($read->viewed_at === null) {
            $read->viewed_at = now();
            $read->role = optional($user->roles->first())->slug;
            $read->school_id = $user->school_id;
            $read->ip_address = $ip;
            $read->device = $device;
            $read->save();
        }
    }

    public function confirmRead(Announcement $announcement, User $user, ?string $ip = null, ?string $device = null): void
    {
        $read = $announcement->reads()->firstOrNew(['user_id' => $user->id]);
        if ($read->viewed_at === null) {
            $read->viewed_at = now();
        }
        $read->read_confirmed_at = now();
        $read->role = $read->role ?? optional($user->roles->first())->slug;
        $read->school_id = $read->school_id ?? $user->school_id;
        $read->ip_address = $read->ip_address ?? $ip;
        $read->device = $read->device ?? $device;
        $read->save();
    }

    public function readLog(Announcement $announcement): array
    {
        $targeted = $this->resolveTargetedUsers($announcement)->keyBy('id');

        $reads = $announcement->reads()->get()->keyBy('user_id');

        $read = collect();
        $unread = collect();

        foreach ($targeted as $id => $user) {
            $row = $reads->get($id);
            if ($row && ($row->read_confirmed_at || $row->viewed_at)) {
                $user->setAttribute('viewed_at', $row->viewed_at);
                $user->setAttribute('read_confirmed_at', $row->read_confirmed_at);
                $user->setAttribute('read_ip', $row->ip_address);
                $read->push($user);
            } else {
                $unread->push($user);
            }
        }

        return ['read' => $read->values(), 'unread' => $unread->values()];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function scopeToSchool(Builder $query, ?int $schoolId): void
    {
        if ($schoolId !== null) {
            $query->where('announcements.school_id', $schoolId);
        }
    }

    protected function syncTargets(Announcement $announcement, array $userTargetIds, array $roleTargetIds): void
    {
        $announcement->targets()->delete();

        $rows = [];
        foreach (array_unique(array_filter($userTargetIds)) as $uid) {
            $rows[] = ['kind' => 'user', 'target_id' => (int) $uid];
        }
        foreach (array_unique(array_filter($roleTargetIds)) as $rid) {
            $rows[] = ['kind' => 'role', 'target_id' => (int) $rid];
        }

        foreach ($rows as $row) {
            $announcement->targets()->create($row);
        }
    }

    protected function filterByEffectiveStatus(Builder $query, string $status): void
    {
        $now = now();

        switch ($status) {
            case 'draft':
                $query->where('status', 'draft');
                break;
            case 'stopped':
                $query->where('status', 'stopped');
                break;
            case 'scheduled':
                $query->where('status', 'published')
                    ->whereNotNull('starts_at')
                    ->where('starts_at', '>', $now);
                break;
            case 'expired':
                $query->where('status', 'published')
                    ->whereNotNull('ends_at')
                    ->where('ends_at', '<', $now);
                break;
            case 'active':
                $query->where('status', 'published')
                    ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                    ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
                break;
            case 'deleted':
                $query->onlyTrashed();
                break;
        }
    }
}
