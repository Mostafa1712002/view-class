<?php

namespace App\Modules\Evaluation\Repositories\Contracts;

use App\Models\Evaluation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EvaluationRepository
{
    /** Evaluations a given evaluator is responsible for (within scope). */
    public function forEvaluator(int $evaluatorId, ?int $schoolId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /** All evaluations of a subject (the evaluated person), respecting scope. */
    public function forSubject(int $subjectId, ?int $schoolId): Collection;

    public function findScoped(int $id, ?int $schoolId): ?Evaluation;

    public function create(array $payload): Evaluation;

    public function update(Evaluation $evaluation, array $payload): Evaluation;
}
