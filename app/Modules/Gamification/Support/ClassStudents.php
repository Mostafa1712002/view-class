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
}
