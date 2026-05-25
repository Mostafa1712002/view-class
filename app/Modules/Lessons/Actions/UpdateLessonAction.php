<?php

namespace App\Modules\Lessons\Actions;

use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Modules\Lessons\DTOs\LessonDto;
use App\Modules\Lessons\Services\LessonConflictGuard;
use Illuminate\Database\QueryException;
use RuntimeException;

final class UpdateLessonAction
{
    public function __construct(private LessonConflictGuard $guard) {}

    public function execute(SchedulePeriod $lesson, LessonDto $dto): SchedulePeriod
    {
        $schedule = Schedule::firstOrCreate(
            [
                'class_id' => $dto->classId,
                'academic_year_id' => $dto->academicYearId,
                'semester' => $dto->semester,
            ],
            [
                'is_active' => true,
            ]
        );

        $this->guard->assertNoConflict(
            $schedule->id,
            $dto->teacherId,
            $dto->dayOfWeek,
            $dto->periodNumber,
            $lesson->id,
        );

        try {
            $lesson->update([
                'schedule_id' => $schedule->id,
                'subject_id' => $dto->subjectId,
                'teacher_id' => $dto->teacherId,
                'substitute_teacher_id' => $dto->substituteTeacherId,
                'day_of_week' => $dto->dayOfWeek,
                'period_number' => $dto->periodNumber,
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
                'room' => $dto->room,
            ]);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || (int) $e->getCode() === 23000) {
                throw new RuntimeException('الحصة محجوزة لهذا اليوم/الفترة');
            }
            throw $e;
        }

        return $lesson->fresh([
            'schedule.classRoom.section',
            'schedule.academicYear',
            'subject',
            'teacher',
        ]);
    }
}
