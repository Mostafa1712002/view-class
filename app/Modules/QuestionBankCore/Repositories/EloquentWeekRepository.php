<?php

namespace App\Modules\QuestionBankCore\Repositories;

use App\Models\StudyWeek;
use App\Modules\QuestionBankCore\Repositories\Contracts\WeekRepository;
use Illuminate\Support\Collection;

class EloquentWeekRepository implements WeekRepository
{
    public function listForTerm(int $termId): Collection
    {
        return StudyWeek::query()
            ->where('academic_term_id', $termId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): ?StudyWeek
    {
        return StudyWeek::query()->with('term')->find($id);
    }

    public function create(array $data): StudyWeek
    {
        return StudyWeek::query()->create($data);
    }

    public function update(StudyWeek $week, array $data): StudyWeek
    {
        $week->update($data);

        return $week->refresh();
    }

    public function delete(StudyWeek $week): bool
    {
        return (bool) $week->delete();
    }

    public function deleteMany(array $ids, int $termId): int
    {
        return StudyWeek::query()
            ->where('academic_term_id', $termId)
            ->whereIn('id', $ids)
            ->delete();
    }

    public function insertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }
        StudyWeek::query()->insert($rows);
    }

    public function numberExists(int $termId, int $number, ?int $ignoreId = null): bool
    {
        return StudyWeek::query()
            ->where('academic_term_id', $termId)
            ->where('sort_order', $number)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function dateOverlaps(int $termId, string $start, string $end, ?int $ignoreId = null): bool
    {
        // Two ranges overlap when start <= other.end AND end >= other.start.
        return StudyWeek::query()
            ->where('academic_term_id', $termId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();
    }
}
