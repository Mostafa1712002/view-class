<?php

namespace App\Modules\QuestionBankCore\Repositories;

use App\Models\BankQuestion;
use App\Models\ExamQuestion;
use App\Models\QuestionBank;
use App\Modules\QuestionBankCore\Repositories\Contracts\QuestionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentQuestionRepository implements QuestionRepository
{
    public function paginate(?int $schoolId, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = BankQuestion::query()
            ->with(['bank:id,name_ar,name_en,school_id', 'creator:id,name,username', 'lesson:id,name_ar'])
            ->whereHas('bank', fn (Builder $q) => $this->scopeBank($q, $schoolId));

        $this->applyFilters($query, $filters);

        return $query->latest('bank_questions.id')->paginate($perPage)->withQueryString();
    }

    public function findScoped(int $questionId, ?int $schoolId): ?BankQuestion
    {
        return BankQuestion::query()
            ->with(['bank:id,name_ar,name_en,school_id'])
            ->whereKey($questionId)
            ->whereHas('bank', fn (Builder $q) => $this->scopeBank($q, $schoolId))
            ->first();
    }

    public function findBankScoped(int $bankId, ?int $schoolId): ?QuestionBank
    {
        return QuestionBank::query()
            ->whereKey($bankId)
            ->where(fn (Builder $q) => $this->scopeBank($q, $schoolId))
            ->first();
    }

    public function banksForScope(?int $schoolId): Collection
    {
        return QuestionBank::query()
            ->where('is_library', false)
            ->where(fn (Builder $q) => $this->scopeBank($q, $schoolId))
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'school_id', 'subject_id']);
    }

    public function isUsedInExam(int $questionId): bool
    {
        // Legacy exams (exam_questions) AND the rebuilt QB exams (qb_exam_questions)
        // both snapshot bank questions; a question referenced by either must be
        // archived rather than hard-deleted.
        $inLegacy = ExamQuestion::query()
            ->where('source_bank_question_id', $questionId)
            ->exists();

        if ($inLegacy) {
            return true;
        }

        return \App\Modules\QuestionBankCore\Models\QbExamQuestion::query()
            ->where('bank_question_id', $questionId)
            ->exists();
    }

    /**
     * School scope for a bank: a school-scoped user sees their own banks plus
     * public ones; a super-admin (null) sees everything. This mirrors the legacy
     * creatorsForSchool()/findScoped() visibility logic.
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
     * @param  Builder<BankQuestion>  $query
     * @param  array<string,mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function (Builder $w) use ($term) {
                $w->where('body_ar', 'like', "%{$term}%")
                  ->orWhere('body_en', 'like', "%{$term}%")
                  ->orWhereHas('skill', fn (Builder $s) => $s->where('name', 'like', "%{$term}%"));
            });
        }

        if (! empty($filters['code'])) {
            $query->where('question_code', 'like', "%{$filters['code']}%");
        }

        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', (int) $filters['subject_id']);
        }

        if (! empty($filters['grade_id'])) {
            $query->where('grade_id', (int) $filters['grade_id']);
        }

        if (! empty($filters['class_id'])) {
            $query->where('class_id', (int) $filters['class_id']);
        }

        if (! empty($filters['semester_id'])) {
            $query->where('semester_id', (int) $filters['semester_id']);
        }

        if (! empty($filters['skill_id'])) {
            $query->where('skill_id', (int) $filters['skill_id']);
        }

        if (! empty($filters['week_id'])) {
            $query->where('week_id', (int) $filters['week_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category'])) {
            $query->where('question_category', $filters['category']);
        }

        if (isset($filters['difficulty']) && $filters['difficulty'] !== '' && $filters['difficulty'] !== null) {
            $query->where('difficulty', (int) $filters['difficulty']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', 'like', "%{$filters['source']}%");
        }

        if (! empty($filters['bank_id'])) {
            $query->where('question_bank_id', (int) $filters['bank_id']);
        }

        if (! empty($filters['has_image'])) {
            $query->whereNotNull('attachment_path');
        }

        if (! empty($filters['full_image_only'])) {
            $query->where('is_full_image_question', true);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }
}
