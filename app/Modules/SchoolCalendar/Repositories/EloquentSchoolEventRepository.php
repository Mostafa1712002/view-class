<?php

namespace App\Modules\SchoolCalendar\Repositories;

use App\Models\SchoolEvent;
use App\Modules\SchoolCalendar\Repositories\Contracts\SchoolEventRepository;
use Illuminate\Support\Collection;

class EloquentSchoolEventRepository implements SchoolEventRepository
{
    public function forRange(?int $schoolId, string $from, string $to, ?string $audienceKey = null): Collection
    {
        $query = SchoolEvent::query()
            ->when($schoolId !== null, fn ($q) => $q->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId)))
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
            ->when($schoolId !== null, fn ($q) => $q->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId)))
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
}
