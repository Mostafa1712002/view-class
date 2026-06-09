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
            ->withCount(['questions', 'sharedSchools']);

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
            ->with(['subjects', 'members', 'sharedSchools:id,name,name_en', 'school:id,name,name_en', 'creator:id,name,username'])
            ->whereKey($id);

        if ($schoolId !== null) {
            $companyId = $this->companyIdForSchool($schoolId);
            $query->where(function ($q) use ($schoolId, $companyId) {
                // Own bank
                $q->where('school_id', $schoolId)
                  // Template library
                  ->orWhere('is_library', true)
                  // General bank of same company — all schools in company (no pivot restriction)
                  ->orWhere(function ($pub) use ($companyId) {
                      $pub->where('visibility', QuestionBank::VISIBILITY_PUBLIC)
                          ->whereDoesntHave('sharedSchools')
                          ->when($companyId !== null, function ($cq) use ($companyId) {
                              $cq->whereHas('school', fn ($s) => $s->where('educational_company_id', $companyId))
                                 ->orWhereNull('school_id'); // super-admin created with no school
                          });
                  })
                  // General bank explicitly shared with this school
                  ->orWhere(function ($pub) use ($schoolId) {
                      $pub->where('visibility', QuestionBank::VISIBILITY_PUBLIC)
                          ->whereHas('sharedSchools', fn ($s) => $s->where('schools.id', $schoolId));
                  });
            });
        }

        return $query->first();
    }

    public function create(array $payload, array $subjectIds, array $memberRoles, array $schoolIds = []): QuestionBank
    {
        return DB::transaction(function () use ($payload, $subjectIds, $memberRoles, $schoolIds) {
            $bank = QuestionBank::create($this->withoutNulls($payload));

            if (! empty($subjectIds)) {
                $bank->subjects()->sync($subjectIds);
            }

            if (! empty($memberRoles)) {
                $bank->members()->sync($this->normalizeMemberRoles($memberRoles));
            }

            $bank->sharedSchools()->sync($this->normalizeSchoolIds($bank, $schoolIds));

            return $bank->load(['subjects', 'members', 'sharedSchools']);
        });
    }

    public function update(QuestionBank $bank, array $payload, ?array $subjectIds, ?array $memberRoles, ?array $schoolIds = null): QuestionBank
    {
        return DB::transaction(function () use ($bank, $payload, $subjectIds, $memberRoles, $schoolIds) {
            $bank->fill($this->withoutNulls($payload));
            $bank->save();

            if ($subjectIds !== null) {
                $bank->subjects()->sync($subjectIds);
            }

            if ($memberRoles !== null) {
                $bank->members()->sync($this->normalizeMemberRoles($memberRoles));
            }

            if ($schoolIds !== null) {
                $bank->sharedSchools()->sync($this->normalizeSchoolIds($bank, $schoolIds));
            }

            return $bank->refresh()->load(['subjects', 'members', 'sharedSchools']);
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

    public function approve(QuestionBank $bank): QuestionBank
    {
        $bank->status = QuestionBank::STATUS_ACTIVE;
        $bank->save();
        return $bank;
    }

    public function promote(QuestionBank $bank, array $schoolIds = []): QuestionBank
    {
        return DB::transaction(function () use ($bank, $schoolIds) {
            $bank->visibility = QuestionBank::VISIBILITY_PUBLIC;
            $bank->status = QuestionBank::STATUS_ACTIVE;
            $bank->save();

            // Set sharing pivot; empty = company-wide
            $bank->sharedSchools()->sync($this->normalizeSchoolIds($bank, $schoolIds));

            return $bank->refresh()->load(['subjects', 'sharedSchools']);
        });
    }

    public function copyToSchool(QuestionBank $generalBank, int $targetSchoolId, int $createdBy): QuestionBank
    {
        return DB::transaction(function () use ($generalBank, $targetSchoolId, $createdBy) {
            $copy = QuestionBank::create([
                'school_id'              => $targetSchoolId,
                'name_ar'                => $generalBank->name_ar . ' — نسخة',
                'name_en'                => $generalBank->name_en ? $generalBank->name_en . ' — copy' : null,
                'description'            => $generalBank->description,
                'is_library'             => false,
                'visibility'             => QuestionBank::VISIBILITY_PRIVATE,
                'status'                 => QuestionBank::STATUS_ACTIVE,
                'source'                 => QuestionBank::SOURCE_MANUAL,
                'grade_level'            => $generalBank->grade_level,
                'category_type'          => $generalBank->category_type,
                'is_ana_qudurat_linkable' => $generalBank->is_ana_qudurat_linkable ?? false,
                'created_by'             => $createdBy,
            ]);

            $copy->subjects()->sync($generalBank->subjects->pluck('id')->all());

            // Only copy approved questions
            foreach ($generalBank->questions()->where('status', 'approved')->get() as $q) {
                $copy->questions()->create([
                    'type'        => $q->type,
                    'body_ar'     => $q->body_ar,
                    'body_en'     => $q->body_en,
                    'answer_data' => $q->answer_data,
                    'difficulty'  => $q->difficulty,
                    'points'      => $q->points,
                    'status'      => 'approved',
                    'created_by'  => $createdBy,
                ]);
            }

            return $copy->load(['subjects']);
        });
    }

    /**
     * Base query for the index: respects school scope and company boundary, excludes templates.
     */
    private function baseQuery(?int $schoolId): Builder
    {
        $query = QuestionBank::query()->where('is_library', false);

        if ($schoolId !== null) {
            $companyId = $this->companyIdForSchool($schoolId);

            $query->where(function ($q) use ($schoolId, $companyId) {
                // 1) The school's own banks (private or public it created).
                $q->where('school_id', $schoolId)
                  // 2) General (public) banks of the same company, available to all company schools (no explicit pivot).
                  ->orWhere(function ($pub) use ($companyId) {
                      $pub->where('visibility', QuestionBank::VISIBILITY_PUBLIC)
                          ->whereDoesntHave('sharedSchools')
                          ->when($companyId !== null, function ($cq) use ($companyId) {
                              $cq->where(function ($inner) use ($companyId) {
                                  $inner->whereHas('school', fn ($s) => $s->where('educational_company_id', $companyId))
                                        ->orWhereNull('school_id');
                              });
                          });
                  })
                  // 3) General banks explicitly shared with this school.
                  ->orWhere(function ($pub) use ($schoolId) {
                      $pub->where('visibility', QuestionBank::VISIBILITY_PUBLIC)
                          ->whereHas('sharedSchools', fn ($s) => $s->where('schools.id', $schoolId));
                  });
            });
        }

        return $query;
    }

    /**
     * Look up the educational_company_id for a given school (cached per request cycle).
     */
    private function companyIdForSchool(int $schoolId): ?int
    {
        static $cache = [];
        if (! array_key_exists($schoolId, $cache)) {
            $cache[$schoolId] = \App\Models\School::query()
                ->whereKey($schoolId)
                ->value('educational_company_id');
        }
        return $cache[$schoolId];
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

    /**
     * Shared-school targeting only makes sense for public (general) banks.
     * For a private bank we always clear the pivot so it stays school-scoped.
     */
    private function normalizeSchoolIds(QuestionBank $bank, array $schoolIds): array
    {
        if ($bank->visibility !== QuestionBank::VISIBILITY_PUBLIC) {
            return [];
        }

        return collect($schoolIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function withoutNulls(array $payload): array
    {
        return array_filter($payload, static fn ($v) => $v !== null && $v !== '');
    }
}
