<?php

namespace App\Modules\QuestionBankCore\Repositories;

use App\Modules\QuestionBankCore\Models\Standard;
use App\Modules\QuestionBankCore\Repositories\Contracts\StandardRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentStandardRepository implements StandardRepository
{
    public function listActive(?int $subjectId = null, ?int $domainId = null): Collection
    {
        return Standard::query()
            ->where('status', 'active')
            ->when($subjectId, fn (Builder $q) => $q->where('subject_id', $subjectId))
            ->when($domainId, fn (Builder $q) => $q->where('domain_id', $domainId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function paginateForAdmin(array $filters, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Standard::query()
            ->with(['subject:id,name', 'domain:id,name'])
            ->when($filters['q'] ?? null, fn (Builder $q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")))
            ->when($filters['subject_id'] ?? null, fn (Builder $q, $v) => $q->where('subject_id', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Standard
    {
        return Standard::query()->with(['subject', 'domain'])->find($id);
    }

    public function create(array $data): Standard
    {
        return Standard::query()->create($data);
    }

    public function update(Standard $standard, array $data): Standard
    {
        $standard->update($data);

        return $standard->refresh();
    }

    public function delete(Standard $standard): bool
    {
        return (bool) $standard->delete();
    }
}
