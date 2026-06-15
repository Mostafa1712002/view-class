<?php

namespace App\Modules\QuestionBankCore\Repositories\Contracts;

use App\Modules\QuestionBankCore\Models\Compound;
use Illuminate\Support\Collection;

interface CompoundRepository
{
    public function listActive(): Collection;

    public function find(int $id): ?Compound;

    public function create(array $data): Compound;

    public function update(Compound $compound, array $data): Compound;

    public function delete(Compound $compound): bool;

    /** Sync the schools linked to a compound. */
    public function syncSchools(Compound $compound, array $schoolIds): void;
}
