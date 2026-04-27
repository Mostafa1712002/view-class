<?php

namespace App\Modules\Users\Repositories;

use App\Models\User;
use App\Modules\Users\Repositories\Contracts\AdminRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAdminListRepository extends BaseUserListRepository implements AdminRepository
{
    /**
     * Admin list spans every non-{student/teacher/parent} role: school-admin, super-admin, plus job-title bearers.
     * We therefore override base() to filter by "has any non-end-user role" rather than a single slug.
     */
    protected function roleSlug(): string
    {
        return 'school-admin';
    }

    protected function indexWith(): array
    {
        return ['jobTitle', 'roles'];
    }

    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $endUserRoles = ['student', 'parent', 'teacher'];

        $q = User::query()
            ->whereHas('roles', fn ($r) => $r->whereNotIn('slug', $endUserRoles))
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('slug', $endUserRoles));

        if ($schoolId !== null) {
            $q->where(function ($w) use ($schoolId) {
                $w->where('users.school_id', $schoolId)
                  ->orWhereHas('roles', fn ($r) => $r->where('slug', 'super-admin'));
            });
        }

        if ($search !== null && trim($search) !== '') {
            $needle = '%'.trim($search).'%';
            $q->where(function ($w) use ($needle) {
                $w->where('users.name', 'like', $needle)
                  ->orWhere('users.username', 'like', $needle)
                  ->orWhere('users.email', 'like', $needle)
                  ->orWhere('users.national_id', 'like', $needle);
            });
        }

        return $q->with($this->indexWith())
            ->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?User
    {
        $endUserRoles = ['student', 'parent', 'teacher'];
        $q = User::query()
            ->whereHas('roles', fn ($r) => $r->whereNotIn('slug', $endUserRoles))
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('slug', $endUserRoles))
            ->where('users.id', $id);
        if ($schoolId !== null) {
            $q->where(function ($w) use ($schoolId) {
                $w->where('users.school_id', $schoolId)
                  ->orWhereHas('roles', fn ($r) => $r->where('slug', 'super-admin'));
            });
        }
        return $q->first();
    }
}
