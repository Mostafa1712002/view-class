<?php

namespace App\Modules\Users\Repositories;

use App\Models\User;
use App\Modules\Users\Repositories\Contracts\UserListRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseUserListRepository implements UserListRepository
{
    /** Role slug this repository filters on (student/teacher/parent/etc.) */
    abstract protected function roleSlug(): string;

    /** Optional extra eager-load relations for index queries. */
    protected function indexWith(): array
    {
        return [];
    }

    /** Optional extra columns to add to the search OR clause. */
    protected function extraSearchColumns(): array
    {
        return [];
    }

    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $this->base($schoolId, $search)
            ->with($this->indexWith())
            ->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?User
    {
        return $this->base($schoolId)->where('users.id', $id)->first();
    }

    protected function base(?int $schoolId, ?string $search = null): Builder
    {
        $q = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', $this->roleSlug()));

        if ($schoolId !== null) {
            $q->where('users.school_id', $schoolId);
        }

        if ($search !== null && trim($search) !== '') {
            $needle = '%'.trim($search).'%';
            $extras = $this->extraSearchColumns();
            $q->where(function ($w) use ($needle, $extras) {
                $w->where('users.name', 'like', $needle)
                  ->orWhere('users.email', 'like', $needle)
                  ->orWhere('users.username', 'like', $needle)
                  ->orWhere('users.national_id', 'like', $needle);
                foreach ($extras as $col) {
                    $w->orWhere('users.'.$col, 'like', $needle);
                }
            });
        }

        return $q;
    }
}
