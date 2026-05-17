<?php

namespace App\Modules\Lessons\DTOs;

final class LessonDto
{
    public function __construct(
        public readonly int $classId,
        public readonly int $academicYearId,
        public readonly string $semester,
        public readonly int $subjectId,
        public readonly int $teacherId,
        public readonly int $dayOfWeek,
        public readonly int $periodNumber,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
        public readonly ?string $room = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            classId: (int) $data['class_id'],
            academicYearId: (int) $data['academic_year_id'],
            semester: (string) $data['semester'],
            subjectId: (int) $data['subject_id'],
            teacherId: (int) $data['teacher_id'],
            dayOfWeek: (int) $data['day_of_week'],
            periodNumber: (int) $data['period_number'],
            startTime: $data['start_time'] ?? null,
            endTime: $data['end_time'] ?? null,
            room: $data['room'] ?? null,
        );
    }
}
