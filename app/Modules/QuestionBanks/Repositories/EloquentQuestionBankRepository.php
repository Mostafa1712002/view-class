<?php

namespace App\Modules\QuestionBanks\Repositories;

use App\Models\QuestionBank;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentQuestionBankRepository implements QuestionBankRepository
{
    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $query = QuestionBank::query()
            ->with(['subjects:id,name,name_en'])
            ->withCount('questions')
            ->where('is_library', false);

        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        if ($search !== null && $search !== '') {
            $needle = '%' . $search . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('name_ar', 'like', $needle)->orWhere('name_en', 'like', $needle);
            });
        }

        return $query->orderBy('name_ar')->paginate($perPage)->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?QuestionBank
    {
        $query = QuestionBank::query()
            ->with(['subjects', 'members'])
            ->whereKey($id);

        if ($schoolId !== null) {
            $query->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhere('is_library', true);
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
                'is_library' => false,
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
        return array_filter($payload, static fn ($v) => $v !== null);
    }
}
