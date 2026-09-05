<?php

namespace App\Modules\Portfolio\Repositories;

use App\Models\PortfolioEntry;
use App\Modules\Portfolio\Repositories\Contracts\PortfolioEntryRepository;
use Illuminate\Support\Collection;

class EloquentPortfolioEntryRepository implements PortfolioEntryRepository
{
    public function groupedForUser(int $userId): Collection
    {
        return PortfolioEntry::where('user_id', $userId)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('section');
    }

    public function create(array $data): PortfolioEntry
    {
        return PortfolioEntry::create($data);
    }

    public function update(PortfolioEntry $entry, array $data): PortfolioEntry
    {
        $entry->update($data);

        return $entry->fresh();
    }

    public function delete(PortfolioEntry $entry): void
    {
        $entry->delete();
    }
}
