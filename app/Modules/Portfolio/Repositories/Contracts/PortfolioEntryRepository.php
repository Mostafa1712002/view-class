<?php

namespace App\Modules\Portfolio\Repositories\Contracts;

use App\Models\PortfolioEntry;
use Illuminate\Support\Collection;

interface PortfolioEntryRepository
{
    /**
     * All entries owned by a user, newest first, grouped by section key —
     * ready for the show view to pull per-section via ->get($section, collect()).
     *
     * @return Collection<string, \Illuminate\Database\Eloquent\Collection<int, PortfolioEntry>>
     */
    public function groupedForUser(int $userId): Collection;

    public function create(array $data): PortfolioEntry;

    public function update(PortfolioEntry $entry, array $data): PortfolioEntry;

    public function delete(PortfolioEntry $entry): void;
}
