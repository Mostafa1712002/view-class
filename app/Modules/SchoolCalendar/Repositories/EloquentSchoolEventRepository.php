<?php

namespace App\Modules\SchoolCalendar\Repositories;

use App\Models\SchoolEvent;
use App\Models\User;
use App\Modules\SchoolCalendar\Repositories\Contracts\SchoolEventRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class EloquentSchoolEventRepository implements SchoolEventRepository
{
    public function forRange(?int $schoolId, string $from, string $to, ?string $audienceKey = null): Collection
    {
        $query = SchoolEvent::query()
            ->with('targets')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->where(function ($q) use ($from, $to) {
                // Event overlaps the range: starts on or before $to AND ends (or starts) on or after $from
                $q->where('start_date', '<=', $to)
                  ->where(function ($inner) use ($from) {
                      $inner->whereNotNull('end_date')
                            ->where('end_date', '>=', $from)
                            ->orWhere('start_date', '>=', $from);
                  });
            });

        if ($audienceKey !== null) {
            $query->where(function ($q) use ($audienceKey) {
                $q->whereJsonContains('audience', 'all')
                  ->orWhereJsonContains('audience', $audienceKey);
            });
        }

        return $query->orderBy('start_date')->get();
    }

    public function all(?int $schoolId): Collection
    {
        return SchoolEvent::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->latest('id')
            ->get();
    }

    public function findById(int $id): ?SchoolEvent
    {
        return SchoolEvent::find($id);
    }

    public function create(array $data): SchoolEvent
    {
        return SchoolEvent::create($data);
    }

    public function update(int $id, array $data): SchoolEvent
    {
        $event = SchoolEvent::findOrFail($id);
        $event->update($data);
        return $event->fresh();
    }

    public function delete(int $id): bool
    {
        $event = SchoolEvent::findOrFail($id);
        return (bool) $event->delete();
    }

    public function syncTargets(SchoolEvent $event, array $userTargetIds, array $roleTargetIds = []): void
    {
        $event->targets()->delete();

        foreach (array_unique(array_filter($userTargetIds)) as $uid) {
            $event->targets()->create(['kind' => 'user', 'target_id' => (int) $uid]);
        }
        foreach (array_unique(array_filter($roleTargetIds)) as $rid) {
            $event->targets()->create(['kind' => 'role', 'target_id' => (int) $rid]);
        }
    }

    public function resolveTargetedUsers(SchoolEvent $event): Collection
    {
        $query = User::query()->where('users.school_id', $event->school_id);

        if (($event->target_type ?? SchoolEvent::TARGET_SCHOOL) === SchoolEvent::TARGET_SPECIFIC) {
            $userIds  = $event->targets->where('kind', 'user')->pluck('target_id')->map(fn ($v) => (int) $v)->all();
            $grades   = $event->grade_levels ?: [];
            $classIds = array_map('intval', $event->class_ids ?: []);

            $query->where(function (Builder $q) use ($userIds, $grades, $classIds) {
                $matched = false;
                if (! empty($userIds)) {
                    $q->orWhereIn('users.id', $userIds);
                    $matched = true;
                }
                if (! empty($classIds)) {
                    $q->orWhereIn('users.class_room_id', $classIds)
                      ->orWhereHas('enrolledClasses', fn ($cq) => $cq->whereIn('classes.id', $classIds));
                    $matched = true;
                }
                if (! empty($grades)) {
                    $q->orWhereExists(function ($sub) use ($grades) {
                        $sub->select(DB::raw(1))
                            ->from('classes')
                            ->whereColumn('classes.id', 'users.class_room_id')
                            ->whereIn('classes.grade_level', $grades);
                    })->orWhereHas('enrolledClasses', fn ($cq) => $cq->whereIn('classes.grade_level', $grades));
                    $matched = true;
                }
                if (! $matched) {
                    $q->whereRaw('1 = 0');
                }
            });

            return $query->get();
        }

        // Whole-school event: narrow by audience role keys.
        $audience = $event->audience ?: ['all'];
        if (! in_array('all', $audience, true)) {
            $slugMap = ['students' => 'student', 'parents' => 'parent', 'teachers' => 'teacher'];
            $slugs   = [];
            foreach ($audience as $aud) {
                if (isset($slugMap[$aud])) {
                    $slugs[] = $slugMap[$aud];
                } elseif ($aud === 'staff') {
                    $slugs[] = 'school-admin';
                }
            }
            $query->whereHas('roles', fn ($q) => $q->whereIn('slug', $slugs ?: ['__none__']));
        }

        return $query->get();
    }
}
