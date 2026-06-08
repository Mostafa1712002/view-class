<?php

namespace App\Modules\Evaluation\Repositories;

use App\Models\Evaluation;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentEvaluationRepository implements EvaluationRepository
{
    public function forEvaluator(int $evaluatorId, ?int $schoolId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->scoped($schoolId)
            ->where('evaluator_id', $evaluatorId)
            ->when(!empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['form_id']), fn (Builder $q) => $q->where('form_id', $filters['form_id']))
            ->with(['form:id,title,type', 'subject:id,name'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function forSubject(int $subjectId, ?int $schoolId): Collection
    {
        return $this->scoped($schoolId)
            ->where('subject_id', $subjectId)
            // NB: load the full form (not a column subset) so $form->setting()
            // can read the `settings` json — otherwise allow_subject_view_results
            // is never seen and the subject's results stay hidden.
            ->with(['form', 'evaluator:id,name'])
            ->latest('id')
            ->get();
    }

    public function findScoped(int $id, ?int $schoolId): ?Evaluation
    {
        return $this->scoped($schoolId)->whereKey($id)->first();
    }

    public function create(array $payload): Evaluation
    {
        return Evaluation::create($payload);
    }

    public function update(Evaluation $evaluation, array $payload): Evaluation
    {
        $evaluation->fill($payload)->save();

        return $evaluation->refresh();
    }

    private function scoped(?int $schoolId): Builder
    {
        $q = Evaluation::query();
        if ($schoolId !== null) {
            $q->where('school_id', $schoolId);
        }

        return $q;
    }
}
