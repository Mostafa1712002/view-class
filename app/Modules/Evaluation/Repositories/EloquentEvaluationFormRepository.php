<?php

namespace App\Modules\Evaluation\Repositories;

use App\Models\EvaluationForm;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentEvaluationFormRepository implements EvaluationFormRepository
{
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->scopedQuery($schoolId, $filters)
            ->withCount(['items', 'indicators', 'targets', 'assignments', 'evaluations'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?EvaluationForm
    {
        return $this->scopedQuery($schoolId)->whereKey($id)->first();
    }

    public function create(array $payload): EvaluationForm
    {
        return EvaluationForm::create($payload);
    }

    public function update(EvaluationForm $form, array $payload): EvaluationForm
    {
        $form->fill($payload)->save();

        return $form->refresh();
    }

    public function delete(EvaluationForm $form): void
    {
        $form->delete();
    }

    /** Apply multi-tenant scope + index filters. */
    private function scopedQuery(?int $schoolId, array $filters = []): Builder
    {
        $q = EvaluationForm::query();

        // Non-super-admin: restrict to the school. Super-admin (null) sees global + all.
        if ($schoolId !== null) {
            $q->where(function (Builder $w) use ($schoolId) {
                $w->where('school_id', $schoolId)->orWhereNull('school_id');
            });
        }

        if (!empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }
        if (!empty($filters['usage_domain'])) {
            $q->where('usage_domain', $filters['usage_domain']);
        }
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (array_key_exists('is_class_visit_only', $filters) && $filters['is_class_visit_only'] !== null && $filters['is_class_visit_only'] !== '') {
            $q->where('is_class_visit_only', (bool) $filters['is_class_visit_only']);
        }
        if (array_key_exists('links_to_job_performance', $filters) && $filters['links_to_job_performance'] !== null && $filters['links_to_job_performance'] !== '') {
            $q->where('links_to_job_performance', (bool) $filters['links_to_job_performance']);
        }
        if (!empty($filters['created_by'])) {
            $q->where('created_by', $filters['created_by']);
        }
        if (!empty($filters['created_from'])) {
            $q->whereDate('created_at', '>=', $filters['created_from']);
        }
        if (!empty($filters['created_to'])) {
            $q->whereDate('created_at', '<=', $filters['created_to']);
        }
        if (!empty($filters['search'])) {
            $q->where('title', 'like', '%'.$filters['search'].'%');
        }

        return $q;
    }
}
