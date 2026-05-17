<?php

namespace App\Modules\Libraries\Repositories;

use App\Models\Library;
use App\Models\LibraryAudience;
use App\Modules\Libraries\Repositories\Contracts\LibraryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentLibraryRepository implements LibraryRepository
{
    public function paginatePrivate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $q = Library::query()->where('type', 'private');
        if ($schoolId) {
            $q->where('school_id', $schoolId);
        }
        if ($search) {
            $q->where('title', 'like', '%' . $search . '%');
        }
        $q->withCount('items', 'audiences');
        return $q->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?Library
    {
        $q = Library::query()->whereKey($id);
        if ($schoolId) {
            $q->where('school_id', $schoolId);
        }
        return $q->first();
    }

    public function create(array $payload): Library
    {
        return Library::create($payload);
    }

    public function update(Library $library, array $payload): Library
    {
        $library->update($payload);
        return $library->fresh();
    }

    public function delete(Library $library): void
    {
        $library->delete();
    }

    public function syncAudiences(Library $library, array $audiences): void
    {
        LibraryAudience::where('library_id', $library->id)->delete();
        $rows = [];
        $now = now();
        foreach ($audiences as $a) {
            if (empty($a['type']) || empty($a['id'])) {
                continue;
            }
            if (! in_array($a['type'], LibraryAudience::TYPES, true)) {
                continue;
            }
            $rows[] = [
                'library_id' => $library->id,
                'audience_type' => $a['type'],
                'audience_id' => (int) $a['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            LibraryAudience::insert($rows);
        }
    }
}
