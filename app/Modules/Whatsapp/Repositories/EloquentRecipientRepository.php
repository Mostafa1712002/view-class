<?php

namespace App\Modules\Whatsapp\Repositories;

use App\Models\ClassRoom;
use App\Models\User;
use App\Modules\Whatsapp\Repositories\Contracts\RecipientRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentRecipientRepository implements RecipientRepository
{
    public function resolveAudience(string $audience, ?int $schoolId, int|string|array|null $refId = null): Collection
    {
        // refs may be one value or many (multi-select); normalise to a clean list.
        $refs = array_values(array_unique(array_filter((array) $refId, fn ($v) => $v !== null && $v !== '')));
        // null = legacy single-ref caller (SMS send re-resolves without its ref and
        // intersects with the posted ids): keep its old "no ref = no filter" meaning.
        // An array (WhatsApp multi-select) with nothing picked resolves to nobody.
        $any = $refs !== [] || $refId === null;

        return match ($audience) {
            'all_students'   => $this->usersByRole('student', $schoolId)->get(),
            'all_teachers'   => $this->usersByRole('teacher', $schoolId)->get(),
            'all_parents'    => $this->usersByRole('parent', $schoolId)->get(),
            // a school-scoped sender can never pick another school's teachers
            'school_teachers' => $this->usersByRole('teacher', $schoolId ?? (((int) ($refs[0] ?? 0)) ?: null))->get(),
            'stage_students'  => $refs ? $this->stageStudents($schoolId, $refs)->get() : collect(),
            'grade_students'  => $any ? $this->gradeStudents($schoolId, $refs)->get() : collect(),
            'class_students'  => $any ? $this->classStudents($schoolId, $refs)->get() : collect(),
            'stage_parents'   => $refs ? $this->parentsOf($this->stageStudents($schoolId, $refs)->get()) : collect(),
            'grade_parents'   => $any ? $this->parentsOf($this->gradeStudents($schoolId, $refs)->get()) : collect(),
            'class_parents'   => $any ? $this->parentsOf($this->classStudents($schoolId, $refs)->get()) : collect(),
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

    public function stagesForSchool(?int $schoolId): array
    {
        return \App\Models\Section::query()
            ->when($schoolId, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->whereNotNull('level')
            ->distinct()
            ->pluck('level')
            ->sortBy(fn ($l) => array_search($l, ['primary', 'intermediate', 'secondary'], true))
            ->mapWithKeys(fn ($l) => [$l => (new \App\Models\Section(['level' => $l]))->level_label])
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

    /** @param array<int|string> $classIds  empty = no class filter (legacy SMS) */
    private function classStudents(?int $schoolId, array $classIds): Builder
    {
        $classIds = array_map('intval', $classIds);

        // a student is enrolled either via users.class_room_id or class_student pivot
        return $this->usersByRole('student', $schoolId)->when($classIds, fn (Builder $q) => $q->where(function (Builder $sub) use ($classIds) {
            $sub->whereIn('class_room_id', $classIds)
                ->orWhereHas('enrolledClasses', fn (Builder $c) => $c->whereIn('classes.id', $classIds));
        }));
    }

    /** @param array<int|string> $gradeLevels  empty = no grade filter (legacy SMS) */
    private function gradeStudents(?int $schoolId, array $gradeLevels): Builder
    {
        $gradeLevels = array_map('intval', $gradeLevels);

        return $this->usersByRole('student', $schoolId)
            ->when($gradeLevels, fn (Builder $q) => $q->whereHas('classRoom', fn (Builder $c) => $c->whereIn('grade_level', $gradeLevels)));
    }

    /** @param array<string> $levels sections.level values (primary|intermediate|secondary) */
    private function stageStudents(?int $schoolId, array $levels): Builder
    {
        return $this->usersByRole('student', $schoolId)
            ->whereHas('classRoom.section', fn (Builder $s) => $s->whereIn('level', $levels));
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
            ->whereHas('children', fn (Builder $q) => $q->whereIn('parent_student.student_id', $students->pluck('id')->all()))
            ->get();
    }
}
