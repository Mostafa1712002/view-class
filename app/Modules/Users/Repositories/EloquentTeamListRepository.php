<?php

namespace App\Modules\Users\Repositories;

use App\Models\User;
use App\Modules\Users\Repositories\Contracts\TeamRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentTeamListRepository implements TeamRepository
{
    private function base(?int $schoolId): Builder
    {
        $q = User::query()
            ->whereHas('roles', fn ($r) => $r->whereNotIn('slug', ['student', 'parent']))
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('slug', ['student', 'parent']));

        if ($schoolId !== null) {
            $q->where(function ($w) use ($schoolId) {
                $w->where('users.school_id', $schoolId)
                  ->orWhereHas('roles', fn ($r) => $r->where('slug', 'super-admin'));
            });
        }

        return $q;
    }

    public function paginate(?int $schoolId, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $q = $this->base($schoolId);

        $type = $filters['type'] ?? null;
        if ($type === 'teacher') {
            $q->whereHas('roles', fn ($r) => $r->where('slug', 'teacher'));
        } elseif ($type === 'admin') {
            $q->whereDoesntHave('roles', fn ($r) => $r->where('slug', 'teacher'));
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $needle = '%'.$search.'%';
            $q->where(function ($w) use ($needle) {
                $w->where('users.name', 'like', $needle)
                  ->orWhere('users.email', 'like', $needle)
                  ->orWhere('users.username', 'like', $needle)
                  ->orWhere('users.national_id', 'like', $needle)
                  ->orWhere('users.employee_id', 'like', $needle);
            });
        }

        if (! empty($filters['job_title_id'])) {
            $q->where('users.job_title_id', (int) $filters['job_title_id']);
        }

        $status = $filters['status'] ?? null;
        if ($status === 'active') {
            $q->where('users.is_active', 1)
              ->where(fn ($w) => $w->whereNull('users.status')->orWhere('users.status', '<>', 'inactive'));
        } elseif ($status === 'inactive') {
            $q->where(fn ($w) => $w->where('users.is_active', 0)->orWhere('users.status', 'inactive'));
        }

        return $q->with(['jobTitle', 'roles', 'school'])
            ->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(?int $schoolId): array
    {
        $base = $this->base($schoolId);

        return [
            'total'    => (clone $base)->count(),
            'teachers' => (clone $base)->whereHas('roles', fn ($r) => $r->where('slug', 'teacher'))->count(),
            'admins'   => (clone $base)->whereDoesntHave('roles', fn ($r) => $r->where('slug', 'teacher'))->count(),
            'active'   => (clone $base)->where('users.is_active', 1)
                ->where(fn ($w) => $w->whereNull('users.status')->orWhere('users.status', '<>', 'inactive'))->count(),
        ];
    }
}
