<?php

namespace App\Modules\Lessons\Repositories;

use App\Models\SchedulePeriod;
use App\Modules\Lessons\Repositories\Contracts\LessonRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentLessonRepository implements LessonRepository
{
    public function paginate(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SchedulePeriod::query()
            ->with([
                'schedule.classRoom.section',
                'schedule.academicYear',
                'subject',
                'teacher',
                'substituteTeacher',
            ])
            ->withCount('students');

        if ($schoolId !== null) {
            $query->whereHas('schedule.classRoom.section', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        if (!empty($filters['class_id'])) {
            $query->whereHas('schedule', function ($q) use ($filters) {
                $q->where('class_id', $filters['class_id']);
            });
        }

        if (!empty($filters['section_id'])) {
            $query->whereHas('schedule.classRoom', function ($q) use ($filters) {
                $q->where('section_id', $filters['section_id']);
            });
        }

        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['day_of_week']) && $filters['day_of_week'] !== '' && $filters['day_of_week'] !== null) {
            $query->where('day_of_week', (int) $filters['day_of_week']);
        }

        if (!empty($filters['search'])) {
            $needle = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($needle) {
                $q->whereHas('subject', fn ($s) => $s->where('name', 'like', $needle))
                    ->orWhereHas('teacher', fn ($t) => $t->where('name', 'like', $needle))
                    ->orWhereHas('schedule.classRoom', fn ($c) => $c->where('name', 'like', $needle));
            });
        }

        return $query
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id, ?int $schoolId): ?SchedulePeriod
    {
        $query = SchedulePeriod::query()
            ->with([
                'schedule.classRoom.section',
                'schedule.academicYear',
                'subject',
                'teacher',
                'substituteTeacher',
            ])
            ->withCount('students')
            ->where('id', $id);

        if ($schoolId !== null) {
            $query->whereHas('schedule.classRoom.section', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        return $query->first();
    }
}
