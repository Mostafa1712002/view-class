<?php

namespace App\Modules\Evaluation\Repositories;

use App\Models\ClassVisit;
use App\Modules\Evaluation\Repositories\Contracts\ClassVisitRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentClassVisitRepository implements ClassVisitRepository
{
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->scoped($schoolId)
            ->when(!empty($filters['teacher_id']), fn (Builder $q) => $q->where('teacher_id', $filters['teacher_id']))
            ->when(!empty($filters['supervisor_id']), fn (Builder $q) => $q->where('supervisor_id', $filters['supervisor_id']))
            ->when(!empty($filters['subject_id']), fn (Builder $q) => $q->where('subject_id', $filters['subject_id']))
            ->when(!empty($filters['class_room_id']), fn (Builder $q) => $q->where('class_room_id', $filters['class_room_id']))
            ->when(!empty($filters['section_id']), fn (Builder $q) => $q->where('section_id', $filters['section_id']))
            ->when(!empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['date_from']), fn (Builder $q) => $q->whereDate('visit_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn (Builder $q) => $q->whereDate('visit_date', '<=', $filters['date_to']))
            ->when(!empty($filters['search']), fn (Builder $q) => $q->whereHas('teacher', fn ($t) => $t->where('name', 'like', '%'.$filters['search'].'%')))
            ->with(['teacher:id,name', 'supervisor:id,name', 'subject:id,name', 'form:id,title'])
            ->latest('visit_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findScoped(int $id, ?int $schoolId): ?ClassVisit
    {
        return $this->scoped($schoolId)->whereKey($id)->first();
    }

    public function create(array $payload): ClassVisit
    {
        return ClassVisit::create($payload);
    }

    public function update(ClassVisit $visit, array $payload): ClassVisit
    {
        $visit->fill($payload)->save();

        return $visit->refresh();
    }

    public function delete(ClassVisit $visit): void
    {
        $visit->delete();
    }

    public function existsForSlot(int $teacherId, ?int $periodId, string $visitDate, ?int $ignoreId = null): bool
    {
        return ClassVisit::query()
            ->where('teacher_id', $teacherId)
            // null period must match IS NULL, not "= NULL" (which never matches)
            ->when($periodId === null, fn (Builder $q) => $q->whereNull('period_id'))
            ->when($periodId !== null, fn (Builder $q) => $q->where('period_id', $periodId))
            ->whereDate('visit_date', $visitDate)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    private function scoped(?int $schoolId): Builder
    {
        $q = ClassVisit::query();
        if ($schoolId !== null) {
            $q->where('school_id', $schoolId);
        }

        return $q;
    }
}
