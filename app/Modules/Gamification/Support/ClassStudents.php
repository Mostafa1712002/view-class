<?php

namespace App\Modules\Gamification\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The one "students of a class" rule for points ratios: users.class_room_id OR the class_student
 * pivot (same union as User::enrolledClassIds()), so a student linked both ways counts once.
 */
final class ClassStudents
{
    /** Narrows a users query to students of $classIds or of classes in $grades (nothing when both are empty). */
    public static function constrain(Builder $q, array $classIds, array $grades = []): Builder
    {
        return $q->where(function (Builder $n) use ($classIds, $grades) {
            $n->whereRaw('1 = 0');
            if ($classIds !== []) {
                $n->orWhereIn('users.class_room_id', $classIds)
                    ->orWhereHas('enrolledClasses', fn ($c) => $c->whereIn('classes.id', $classIds));
            }
            if ($grades !== []) {
                $n->orWhereHas('classRoom', fn ($c) => $c->whereIn('grade_level', $grades))
                    ->orWhereHas('enrolledClasses', fn ($c) => $c->whereIn('classes.grade_level', $grades));
            }
        });
    }

    /** @return list<int> student ids of one class; empty when there is no class */
    public static function ids(?int $classId, ?int $schoolId = null): array
    {
        if (! $classId) {
            return [];
        }

        $q = User::query()->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
            ->when($schoolId, fn ($q) => $q->where('users.school_id', $schoolId));

        return self::constrain($q, [$classId])->pluck('users.id')->map(fn ($v) => (int) $v)->all();
    }

    /** Narrows a users query to students whose grade (section) is $sectionId: own section_id or their class's section. */
    public static function inSection(Builder $q, int $sectionId): Builder
    {
        return $q->where(fn (Builder $n) => $n->where('users.section_id', $sectionId)
            ->orWhereHas('classRoom', fn ($c) => $c->where('section_id', $sectionId))
            ->orWhereHas('enrolledClasses', fn ($c) => $c->where('classes.section_id', $sectionId)));
    }

    /** Eager loads for placement(). */
    public const PLACEMENT_WITH = ['section:id,name', 'classRoom.section:id,name', 'enrolledClasses.section:id,name'];

    /** @return array{grade: ?string, class: ?string} the student's grade (section) and class, home class first. */
    public static function placement(User $u): array
    {
        $class = $u->classRoom ?? $u->enrolledClasses->first();

        return [
            'grade' => $class?->section?->name ?? $u->section?->name,
            'class' => $class ? trim($class->name.($class->division ? ' - '.$class->division : '')) : null,
        ];
    }
}
