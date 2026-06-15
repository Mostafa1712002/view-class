<?php

namespace App\Modules\Admissions\Repositories;

use App\Modules\Admissions\Models\AdmissionApplication;
use App\Modules\Admissions\Repositories\Contracts\AdmissionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation. The school-scope filter lives HERE (CLAUDE.md rule:
 * multi-tenant scope enforced in the repository, never scattered in controllers).
 */
class EloquentAdmissionRepository implements AdmissionRepository
{
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($schoolId, $filters)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function all(?int $schoolId, array $filters = []): Collection
    {
        return $this->query($schoolId, $filters)->latest('id')->get();
    }

    public function find(int $id, ?int $schoolId): ?AdmissionApplication
    {
        return AdmissionApplication::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->find($id);
    }

    public function statusCounts(?int $schoolId): array
    {
        return AdmissionApplication::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->selectRaw('status, COUNT(*) c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();
    }

    public function create(array $attributes): AdmissionApplication
    {
        return AdmissionApplication::create($attributes);
    }

    public function update(AdmissionApplication $application, array $attributes): AdmissionApplication
    {
        $application->update($attributes);

        return $application->refresh();
    }

    public function delete(AdmissionApplication $application): void
    {
        $application->delete();
    }

    public function nextCode(): string
    {
        do {
            $code = 'ADM-'.now()->format('y').'-'.strtoupper(\Illuminate\Support\Str::random(6));
        } while (AdmissionApplication::where('code', $code)->exists());

        return $code;
    }

    /** Shared scoped + filtered builder. */
    private function query(?int $schoolId, array $filters): Builder
    {
        return AdmissionApplication::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->when($filters['status'] ?? null, fn (Builder $q, $s) => $q->where('status', $s))
            ->when($filters['city'] ?? null, fn (Builder $q, $c) => $q->where('city', 'like', "%{$c}%"))
            ->when($filters['q'] ?? null, function (Builder $q, $term) {
                $q->where(function (Builder $w) use ($term) {
                    $like = "%{$term}%";
                    $w->where('code', 'like', $like)
                        ->orWhere('student_name', 'like', $like)
                        ->orWhere('guardian_name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('national_id', 'like', $like)
                        ->orWhere('hijri_code', 'like', $like);
                });
            });
    }
}
