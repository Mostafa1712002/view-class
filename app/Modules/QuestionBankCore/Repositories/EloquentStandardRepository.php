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
