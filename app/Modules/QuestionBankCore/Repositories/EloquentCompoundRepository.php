<?php

namespace App\Modules\QuestionBankCore\Repositories;

use App\Modules\QuestionBankCore\Models\Compound;
use App\Modules\QuestionBankCore\Repositories\Contracts\CompoundRepository;
use Illuminate\Support\Collection;

class EloquentCompoundRepository implements CompoundRepository
{
    public function listActive(): Collection
    {
        return Compound::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();
    }

    public function paginateForAdmin(array $filters, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Compound::query()
            ->with('schools:id,name,name_ar')
            ->withCount('schools')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name_ar', 'like', "%{$term}%")->orWhere('name_en', 'like', "%{$term}%")))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Compound
    {
        return Compound::query()->with('schools')->find($id);
    }

    public function create(array $data): Compound
    {
        return Compound::query()->create($data);
    }

    public function update(Compound $compound, array $data): Compound
    {
        $compound->update($data);

        return $compound->refresh();
    }

    public function delete(Compound $compound): bool
    {
        return (bool) $compound->delete();
    }

    public function syncSchools(Compound $compound, array $schoolIds): void
    {
        $compound->schools()->sync($schoolIds);
    }
}
