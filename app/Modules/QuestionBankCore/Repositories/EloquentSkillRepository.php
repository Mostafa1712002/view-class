<?php

namespace App\Modules\QuestionBankCore\Repositories;

use App\Modules\QuestionBankCore\Models\Skill;
use App\Modules\QuestionBankCore\Repositories\Contracts\SkillRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentSkillRepository implements SkillRepository
{
    public function listForScope(?int $schoolId): Collection
    {
        return $this->scopedQuery($schoolId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function listForQuestionForm(?int $schoolId, ?int $subjectId, ?int $semesterId, ?int $weekId): Collection
    {
        return $this->scopedQuery($schoolId)
            ->where('status', 'active')
            ->when($subjectId, fn (Builder $q) => $q->where('subject_id', $subjectId))
            ->when($semesterId, fn (Builder $q) => $q->where('semester_id', $semesterId))
            ->when($weekId, fn (Builder $q) => $q->where('week_id', $weekId))
            ->orderBy('name')
            ->get();
    }

    public function paginateForAdmin(?int $schoolId, array $filters, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->scopedQuery($schoolId)
            ->with(['subject:id,name', 'semester:id,name', 'week:id,name'])
            ->when($filters['q'] ?? null, fn (Builder $q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($filters['subject_id'] ?? null, fn (Builder $q, $v) => $q->where('subject_id', $v))
            ->when($filters['semester_id'] ?? null, fn (Builder $q, $v) => $q->where('semester_id', $v))
            ->when($filters['skill_type'] ?? null, fn (Builder $q, $v) => $q->where('skill_type', $v))
            ->when(($filters['status'] ?? null), fn (Builder $q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function insertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }
        Skill::query()->insert($rows);
    }

    public function find(int $id): ?Skill
    {
        return Skill::query()->with(['subject', 'semester', 'week', 'assignments'])->find($id);
    }

    public function create(array $data): Skill
    {
        return Skill::query()->create($data);
    }

    public function update(Skill $skill, array $data): Skill
    {
        $skill->update($data);

        return $skill->refresh();
    }

    public function delete(Skill $skill): bool
    {
        return (bool) $skill->delete();
    }

    /**
     * School scope, fail-open only for super-admin (null scope). A scoped query
     * returns the school's own rows plus globally shared rows (school_id null).
     */
    private function scopedQuery(?int $schoolId): Builder
    {
        $query = Skill::query();

        if ($schoolId !== null) {
            $query->where(function (Builder $q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhereNull('school_id');
            });
        }

        return $query;
    }
}
