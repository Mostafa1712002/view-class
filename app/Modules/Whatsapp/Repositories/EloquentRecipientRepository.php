<?php

namespace App\Modules\Whatsapp\Repositories;

use App\Models\ClassRoom;
use App\Models\User;
use App\Modules\Whatsapp\Repositories\Contracts\RecipientRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentRecipientRepository implements RecipientRepository
{
    public function resolveAudience(string $audience, ?int $schoolId, ?int $refId = null): Collection
    {
        return match ($audience) {
            'all_students'   => $this->usersByRole('student', $schoolId)->get(),
            'all_teachers'   => $this->usersByRole('teacher', $schoolId)->get(),
            'all_parents'    => $this->usersByRole('parent', $schoolId)->get(),
            'school_teachers' => $this->usersByRole('teacher', $refId ?: $schoolId)->get(),
            'grade_students'  => $this->gradeStudents($schoolId, $refId)->get(),
            'class_students'  => $this->classStudents($schoolId, $refId)->get(),
            'grade_parents'   => $this->parentsOf($this->gradeStudents($schoolId, $refId)->get()),
            'class_parents'   => $this->parentsOf($this->classStudents($schoolId, $refId)->get()),
            default           => collect(),
        };
    }

    public function findUsers(array $ids, ?int $schoolId): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return collect();
        }

        return User::query()
            ->with('roles')
            ->whereIn('id', $ids)
            ->when($schoolId, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->get();
    }

    public function classesForSchool(?int $schoolId): Collection
    {
        return ClassRoom::query()
            ->with('section:id,name')
            ->when($schoolId, fn (Builder $q) => $q->whereHas(
                'section',
                fn (Builder $s) => $s->where('school_id', $schoolId)
            ))
            ->orderBy('name')
            ->get(['id', 'name', 'section_id', 'grade_level', 'division']);
    }

    public function gradeLevelsForSchool(?int $schoolId): array
    {
        return User::query()
            ->whereHas('roles', fn (Builder $q) => $q->where('slug', 'student'))
            ->whereNotNull('class_room_id')
            ->when($schoolId, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->with('classRoom:id,grade_level')
            ->get()
            ->pluck('classRoom.grade_level')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    // ---- internals -------------------------------------------------------

    private function usersByRole(string $roleSlug, ?int $schoolId): Builder
    {
        return User::query()
            ->with('roles')
            ->whereHas('roles', fn (Builder $q) => $q->where('slug', $roleSlug))
            ->when($schoolId, fn (Builder $q) => $q->where('school_id', $schoolId));
    }

    private function classStudents(?int $schoolId, ?int $classId): Builder
    {
        $q = $this->usersByRole('student', $schoolId);

        if ($classId) {
            // a student is enrolled either via users.class_room_id or class_student pivot
            $q->where(function (Builder $sub) use ($classId) {
                $sub->where('class_room_id', $classId)
                    ->orWhereHas('enrolledClasses', fn (Builder $c) => $c->where('classes.id', $classId));
            });
        }

        return $q;
    }

    private function gradeStudents(?int $schoolId, ?int $gradeLevel): Builder
    {
        $q = $this->usersByRole('student', $schoolId);

        if ($gradeLevel !== null) {
            $q->whereHas('classRoom', fn (Builder $c) => $c->where('grade_level', $gradeLevel));
        }

        return $q;
    }

    /**
     * Collect the parents of a set of students.
     *
     * @param  Collection<int, User>  $students
     * @return Collection<int, User>
     */
    private function parentsOf(Collection $students): Collection
    {
        if ($students->isEmpty()) {
            return collect();
        }

        return User::query()
            ->with('roles')
            ->whereHas('children', fn (Builder $q) => $q->whereIn('users.id', $students->pluck('id')->all()))
            ->get();
    }
}
