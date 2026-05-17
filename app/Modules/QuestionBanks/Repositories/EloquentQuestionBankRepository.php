<?php

namespace App\Modules\QuestionBanks\Repositories;

use App\Models\QuestionBank;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentQuestionBankRepository implements QuestionBankRepository
{
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->baseQuery($schoolId)
            ->with([
                'subjects:id,name,name_en',
                'school:id,name,name_en',
                'creator:id,name,username',
            ])
            ->withCount('questions');

        $search = $filters['q'] ?? null;
        if ($search !== null && $search !== '') {
            $needle = '%' . $search . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('name_ar', 'like', $needle)->orWhere('name_en', 'like', $needle);
            });
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['grade_level'])) {
            $query->where('grade_level', (int) $filters['grade_level']);
        }

        if (! empty($filters['subject_id'])) {
            $subjectId = (int) $filters['subject_id'];
            $query->whereHas('subjects', fn ($q) => $q->where('subjects.id', $subjectId));
        }

        if (! empty($filters['creator_id'])) {
            $query->where('created_by', (int) $filters['creator_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    public function stats(?int $schoolId): array
    {
        $base = fn () => $this->baseQuery($schoolId);

        return [
            'total' => (clone $base())->count(),
            'public' => (clone $base())->where('visibility', QuestionBank::VISIBILITY_PUBLIC)->count(),
            'private' => (clone $base())->where('visibility', QuestionBank::VISIBILITY_PRIVATE)->count(),
            'active' => (clone $base())->where('status', QuestionBank::STATUS_ACTIVE)->count(),
        ];
    }

    public function findScoped(int $id, ?int $schoolId): ?QuestionBank
    {
        $query = QuestionBank::query()
            ->with(['subjects', 'members', 'school:id,name,name_en', 'creator:id,name,username'])
            ->whereKey($id);

        if ($schoolId !== null) {
            $query->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhere('is_library', true)
                  ->orWhere('visibility', QuestionBank::VISIBILITY_PUBLIC);
            });
        }

        return $query->first();
    }

    public function create(array $payload, array $subjectIds, array $memberRoles): QuestionBank
    {
        return DB::transaction(function () use ($payload, $subjectIds, $memberRoles) {
            $bank = QuestionBank::create($this->withoutNulls($payload));

            if (! empty($subjectIds)) {
                $bank->subjects()->sync($subjectIds);
            }

            if (! empty($memberRoles)) {
                $bank->members()->sync($this->normalizeMemberRoles($memberRoles));
            }

            return $bank->load(['subjects', 'members']);
        });
    }

    public function update(QuestionBank $bank, array $payload, ?array $subjectIds, ?array $memberRoles): QuestionBank
    {
        return DB::transaction(function () use ($bank, $payload, $subjectIds, $memberRoles) {
            $bank->fill($this->withoutNulls($payload));
            $bank->save();

            if ($subjectIds !== null) {
                $bank->subjects()->sync($subjectIds);
            }

            if ($memberRoles !== null) {
                $bank->members()->sync($this->normalizeMemberRoles($memberRoles));
            }

            return $bank->refresh()->load(['subjects', 'members']);
        });
    }

    public function delete(QuestionBank $bank): void
    {
        $bank->delete();
    }

    public function library(): LengthAwarePaginator
    {
        return QuestionBank::query()
            ->with(['subjects:id,name,name_en'])
            ->withCount('questions')
            ->where('is_library', true)
            ->orderBy('name_ar')
            ->paginate(25)
            ->withQueryString();
    }

    public function clone(QuestionBank $template, ?int $schoolId, ?int $createdBy): QuestionBank
    {
        return DB::transaction(function () use ($template, $schoolId, $createdBy) {
            $copy = QuestionBank::create([
                'school_id' => $schoolId,
                'name_ar' => $template->name_ar,
                'name_en' => $template->name_en,
                'description' => $template->description,
                'is_library' => false,
                'visibility' => QuestionBank::VISIBILITY_PRIVATE,
                'status' => QuestionBank::STATUS_ACTIVE,
                'source' => QuestionBank::SOURCE_LIBRARY,
                'grade_level' => $template->grade_level,
                'category_type' => $template->category_type,
                'is_ana_qudurat_linkable' => $template->is_ana_qudurat_linkable ?? false,
                'created_by' => $createdBy,
            ]);

            $copy->subjects()->sync($template->subjects->pluck('id')->all());

            foreach ($template->questions as $q) {
                $copy->questions()->create([
                    'type' => $q->type,
                    'body_ar' => $q->body_ar,
                    'body_en' => $q->body_en,
                    'answer_data' => $q->answer_data,
                    'difficulty' => $q->difficulty,
                ]);
            }

            return $copy->load(['subjects', 'members']);
        });
    }

    /**
     * Base query for the index: respects school scope, excludes templates.
     */
    private function baseQuery(?int $schoolId): Builder
    {
        $query = QuestionBank::query()->where('is_library', false);

        if ($schoolId !== null) {
            $query->where(function ($q) use ($schoolId) {
                // Visible to this school: own private banks + platform-wide public banks
                $q->where('school_id', $schoolId)
                  ->orWhere('visibility', QuestionBank::VISIBILITY_PUBLIC);
            });
        }

        return $query;
    }

    /**
     * Convert flat ['user_id' => 'role'] array into sync format.
     */
    private function normalizeMemberRoles(array $memberRoles): array
    {
        $sync = [];
        foreach ($memberRoles as $userId => $role) {
            if ($role === '' || $role === null) {
                continue;
            }
            $sync[(int) $userId] = ['role' => in_array($role, ['viewer', 'editor'], true) ? $role : 'viewer'];
        }
        return $sync;
    }

    private function withoutNulls(array $payload): array
    {
        return array_filter($payload, static fn ($v) => $v !== null && $v !== '');
    }
}
