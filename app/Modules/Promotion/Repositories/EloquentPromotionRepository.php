<?php

namespace App\Modules\Promotion\Repositories;

use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Modules\Promotion\Repositories\Contracts\PromotionRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentPromotionRepository implements PromotionRepository
{
    public function gradesIndex(School $school): Collection
    {
        return Section::where('school_id', $school->id)
            ->where('is_active', true)
            ->whereNotNull('grade_number')
            ->get()
            ->keyBy('grade_number');
    }

    public function classesOfGradeForYear(int $sectionId, int $yearId): Collection
    {
        return ClassRoom::where('section_id', $sectionId)
            ->where('academic_year_id', $yearId)
            ->orderBy('number')
            ->get();
    }

    public function classInGrade(int $sectionId, int $yearId, int $number, string $gender): ?ClassRoom
    {
        return ClassRoom::where('section_id', $sectionId)
            ->where('academic_year_id', $yearId)
            ->where('number', $number)
            ->where('gender', $gender)
            ->first();
    }

    public function passedStudents(ClassRoom $class): Collection
    {
        return $class->students()
            ->wherePivot('result', 'passed')
            ->orderByRaw('COALESCE(users.name_ar, users.name)')
            ->get();
    }

    public function occupiedSeats(ClassRoom $class): int
    {
        return $class->students()->count();
    }

    public function detachStudent(int $classId, int $studentId): void
    {
        DB::table('class_student')
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->delete();
    }

    public function attachStudent(int $classId, int $studentId, string $result = 'pending'): void
    {
        DB::table('class_student')->updateOrInsert(
            ['class_id' => $classId, 'student_id' => $studentId],
            ['result' => $result, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    public function setPlacement(int $studentId, ?int $sectionId, ?int $classId): void
    {
        User::whereKey($studentId)->update([
            'section_id' => $sectionId,
            'class_room_id' => $classId,
        ]);
    }

    public function setGraduated(int $studentId, bool $graduated): void
    {
        User::whereKey($studentId)->update([
            'graduated_at' => $graduated ? now() : null,
        ]);
    }
}
