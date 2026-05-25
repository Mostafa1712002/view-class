<?php

namespace App\Modules\Lessons\Actions;

use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Modules\Lessons\DTOs\LessonDto;
use App\Modules\Lessons\Services\LessonConflictGuard;
use Illuminate\Database\QueryException;
use RuntimeException;

/**
 * Creates a lesson (schedule_period). Auto-resolves the underlying Schedule
 * from (class_id, academic_year_id, semester) via firstOrCreate so admins
 * can author lessons without having to set up a schedule first.
 */
final class CreateLessonAction
{
    public function __construct(private LessonConflictGuard $guard) {}

    public function execute(LessonDto $dto): SchedulePeriod
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
        );

        try {
            return SchedulePeriod::create([
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
            // Unique index (schedule_id, day_of_week, period_number) violation
            if (str_contains($e->getMessage(), 'Duplicate') || (int) $e->getCode() === 23000) {
                throw new RuntimeException('الحصة محجوزة لهذا اليوم/الفترة');
            }
            throw $e;
        }
    }
}
