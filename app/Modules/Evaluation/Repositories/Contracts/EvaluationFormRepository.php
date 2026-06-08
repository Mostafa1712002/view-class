<?php

namespace App\Modules\Evaluation\Repositories\Contracts;

use App\Models\EvaluationForm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EvaluationFormRepository
{
    /** Paginate forms within the active school scope, applying index filters. */
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findScoped(int $id, ?int $schoolId): ?EvaluationForm;

    public function create(array $payload): EvaluationForm;

    public function update(EvaluationForm $form, array $payload): EvaluationForm;

    public function delete(EvaluationForm $form): void;
}
