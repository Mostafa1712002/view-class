<?php

namespace App\Modules\ClassPeriods\Services;

use App\Models\ClassPeriod;
use App\Models\ScheduleEntry;
use Illuminate\Support\Facades\DB;

class ScheduleConflictDetector
{
    public function teacherHasConflict(int $teacherId, int $timeSlotId, int $dayOfWeek, ?int $excludeEntryId = null): bool
    {
        return ScheduleEntry::query()
            ->whereHas('classPeriod', fn ($q) => $q->where('teacher_id', $teacherId))
            ->where('time_slot_id', $timeSlotId)
            ->where('day_of_week', $dayOfWeek)
            ->when($excludeEntryId, fn ($q) => $q->where('id', '!=', $excludeEntryId))
            ->exists();
    }

    public function classroomHasConflict(int $classRoomId, int $timeSlotId, int $dayOfWeek, ?int $excludeEntryId = null): bool
    {
        return ScheduleEntry::query()
            ->whereHas('classPeriod', fn ($q) => $q->where('class_id', $classRoomId))
            ->where('time_slot_id', $timeSlotId)
            ->where('day_of_week', $dayOfWeek)
            ->when($excludeEntryId, fn ($q) => $q->where('id', '!=', $excludeEntryId))
            ->exists();
    }

    public function describeConflict(ClassPeriod $period, int $timeSlotId, int $dayOfWeek): ?string
    {
        if ($this->teacherHasConflict($period->teacher_id, $timeSlotId, $dayOfWeek)) {
            return 'TEACHER_BUSY';
        }
        if ($this->classroomHasConflict($period->class_id, $timeSlotId, $dayOfWeek)) {
            return 'CLASSROOM_BUSY';
        }
        return null;
    }
}
