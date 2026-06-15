<?php

namespace App\Modules\QuestionBankCore\Repositories;

use App\Modules\QuestionBankCore\Models\Passage;
use App\Modules\QuestionBankCore\Repositories\Contracts\PassageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentPassageRepository implements PassageRepository
{
    public function paginate(?int $schoolId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Passage::query()
            ->with(['bank:id,name_ar,school_id', 'subject:id,name'])
            ->withCount('questions')
            ->whereHas('bank', fn (Builder $q) => $this->scopeBank($q, $schoolId));

        $this->applyFilters($query, $filters);

        return $query->latest('passages.id')->paginate($perPage)->withQueryString();
    }

    public function findScoped(int $passageId, ?int $schoolId): ?Passage
    {
        return Passage::query()
            ->with(['bank:id,name_ar,school_id', 'subject:id,name', 'questions.answers'])
            ->whereKey($passageId)
            ->whereHas('bank', fn (Builder $q) => $this->scopeBank($q, $schoolId))
            ->first();
    }

    /**
     * School scope through the parent bank — mirrors EloquentQuestionRepository.
     */
    private function scopeBank(Builder $q, ?int $schoolId): Builder
    {
        if ($schoolId !== null) {
            $q->where(function (Builder $w) use ($schoolId) {
                $w->where('school_id', $schoolId)
                  ->orWhere('visibility', 'public');
            });
        }

        return $q;
    }

    /**
     * @param  Builder<Passage>  $query
     * @param  array<string,mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $query->where('passage_text', 'like', "%{$filters['q']}%");
        }
        if (! empty($filters['code'])) {
            $query->where('passage_code', 'like', "%{$filters['code']}%");
        }
        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', (int) $filters['subject_id']);
        }
        if (! empty($filters['bank_id'])) {
            $query->where('question_bank_id', (int) $filters['bank_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }
}
