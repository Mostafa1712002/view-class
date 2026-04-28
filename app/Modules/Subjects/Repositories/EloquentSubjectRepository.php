<?php

namespace App\Modules\Subjects\Repositories;

use App\Models\Subject;
use App\Modules\Subjects\Repositories\Contracts\SubjectRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSubjectRepository implements SubjectRepository
{
    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $query = Subject::query()
            ->with(['units' => fn ($q) => $q->withCount('lessons')])
            ->withCount('units');

        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        if ($search !== null && $search !== '') {
            $needle = '%' . $search . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhere('name_en', 'like', $needle)
                    ->orWhere('code', 'like', $needle)
                    ->orWhere('section', 'like', $needle);
            });
        }

        return $query->orderBy('certificate_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?Subject
    {
        $query = Subject::query()->with('units.lessons')->whereKey($id);

        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        return $query->first();
    }

    public function create(array $payload): Subject
    {
        return Subject::create($this->withoutNulls($payload));
    }

    public function update(Subject $subject, array $payload): Subject
    {
        $subject->fill($this->withoutNulls($payload));
        $subject->save();

        return $subject->refresh();
    }

    public function delete(Subject $subject): void
    {
        $subject->delete();
    }

    public function bulkSetCreditHours(?int $schoolId, array $creditHoursById): int
    {
        $count = 0;
        foreach ($creditHoursById as $id => $hours) {
            $query = Subject::query()->whereKey((int) $id);

            if ($schoolId !== null) {
                $query->where('school_id', $schoolId);
            }

            $count += $query->update(['credit_hours' => $hours === '' ? null : (int) $hours]);
        }

        return $count;
    }

    public function platformTemplates(): iterable
    {
        return Subject::query()->whereNull('school_id')->orderBy('name')->get();
    }

    private function withoutNulls(array $payload): array
    {
        return array_filter($payload, static fn ($v) => $v !== null);
    }
}
