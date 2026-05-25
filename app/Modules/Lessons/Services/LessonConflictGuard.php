<?php

namespace App\Modules\Lessons\Services;

use App\Models\SchedulePeriod;
use RuntimeException;

/**
 * Enforces the scheduling constraints from the card spec ("الضوابط المهمة"):
 *  - a teacher cannot run two lessons in the same day + period (across classes).
 *  - a class cannot have two lessons in the same day + period.
 *
 * The DB unique index only covers (schedule_id, day_of_week, period_number),
 * i.e. one classroom-schedule — it does not catch a teacher booked in two
 * different classes at once, which this guard handles.
 */
final class LessonConflictGuard
{
    public function assertNoConflict(
        int $scheduleId,
        int $teacherId,
        int $dayOfWeek,
        int $periodNumber,
        ?int $excludeId = null
    ): void {
        // Same classroom-schedule, same day/period already taken.
        $classBusy = SchedulePeriod::query()
            ->where('schedule_id', $scheduleId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($classBusy) {
            throw new RuntimeException('يوجد حصة أخرى لنفس الفصل في نفس اليوم والفترة.');
        }

        // Same teacher booked in the same day/period in any class.
        $teacherBusy = SchedulePeriod::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('period_number', $periodNumber)
            ->where(function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                    ->orWhere('substitute_teacher_id', $teacherId);
            })
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($teacherBusy) {
            throw new RuntimeException('المعلم لديه حصة أخرى في نفس اليوم والفترة الزمنية.');
        }
    }
}
