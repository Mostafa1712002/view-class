<?php

namespace App\Modules\Libraries\Repositories;

use App\Models\LibraryItem;
use App\Modules\Libraries\Repositories\Contracts\LibraryItemRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentLibraryItemRepository implements LibraryItemRepository
{
    public function paginatePublic(?int $schoolId, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $q = LibraryItem::query()->where('is_public', true);

        // Public library items may be platform-wide (school_id null) or scoped to the user's school
        $q->where(function ($w) use ($schoolId) {
            $w->whereNull('school_id');
            if ($schoolId) {
                $w->orWhere('school_id', $schoolId);
            }
        });

        if (! empty($filters['title'])) {
            $q->where('title', 'like', '%'.$filters['title'].'%');
        }
        if (! empty($filters['content_type'])) {
            $q->where('content_type', $filters['content_type']);
        }
        if (! empty($filters['subject_id'])) {
            $q->where('subject_id', (int) $filters['subject_id']);
        }
        if (! empty($filters['teacher_id'])) {
            $q->where('teacher_id', (int) $filters['teacher_id']);
        }
        if (! empty($filters['tag'])) {
            $q->where('tags', 'like', '%'.$filters['tag'].'%');
        }

        $q->withAvg('ratings as ratings_avg', 'rating')->withCount('ratings');

        if (($filters['sort'] ?? '') === 'top_rated') {
            $q->orderByDesc('ratings_avg')->orderByDesc('id');
        } elseif (($filters['sort'] ?? '') === 'oldest') {
            $q->orderBy('id'); // oldest first
        } else {
            $q->orderByDesc('id'); // newest first (default)
        }

        return $q->paginate($perPage)->withQueryString();
    }

    public function paginateForLibrary(int $libraryId, ?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $q = LibraryItem::query()->where('library_id', $libraryId);
        if ($schoolId) {
            $q->where(function ($w) use ($schoolId) {
                $w->whereNull('school_id')->orWhere('school_id', $schoolId);
            });
        }
        if ($search) {
            $q->where('title', 'like', '%'.$search.'%');
        }

        return $q->orderBy('sort_order')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?LibraryItem
    {
        $q = LibraryItem::query()->whereKey($id);
        if ($schoolId) {
            $q->where(function ($w) use ($schoolId) {
                $w->whereNull('school_id')->orWhere('school_id', $schoolId);
            });
        }

        return $q->first();
    }

    public function create(array $payload): LibraryItem
    {
        return LibraryItem::create($payload);
    }

    public function update(LibraryItem $item, array $payload): LibraryItem
    {
        $item->update($payload);

        return $item->fresh();
    }

    public function delete(LibraryItem $item): void
    {
        $item->delete();
    }
}
