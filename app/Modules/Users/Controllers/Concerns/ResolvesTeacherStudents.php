<?php

namespace App\Modules\Users\Controllers\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Resolves the student IDs a teacher is authorised to view.
 *
 * Copied verbatim from
 * App\Modules\Behavior\Controllers\BehaviorRecordController::teachingStudentIds()
 * (added for card #192) and extracted here so all teacher-facing controllers
 * can reuse it without duplicating the logic.
 *
 * The teacher ⇄ class link is recorded in four places:
 *   1. classes.lead_teacher_id                          (class leader)
 *   2. schedule_periods.teacher_id → schedules.class_id (timetable)
 *   3. subject_teacher.section_id  → classes.section_id (section assignment)
 *   4. class_teacher.class_id                           (التخصيص panel, card #318)
 *
 * Students are matched via both enrolment sources:
 *   - users.class_room_id  (direct column — updated by student-edit form)
 *   - class_student pivot  (set on creation and batch imports)
 */
trait ResolvesTeacherStudents
{
    /**
     * Return an array of student user IDs that the given teacher teaches,
     * optionally scoped to a school.
     *
     * @return array<int>
     */
    protected function teachingStudentIds(int $teacherId, ?int $schoolId): array
    {
        $classIds = collect();

        // 1) classes they lead
        $classIds = $classIds->merge(
            DB::table('classes')->where('lead_teacher_id', $teacherId)->pluck('id')
        );

        // 2) classes on their timetable
        $classIds = $classIds->merge(
            DB::table('schedule_periods')
                ->join('schedules', 'schedules.id', '=', 'schedule_periods.schedule_id')
                ->where('schedule_periods.teacher_id', $teacherId)
                ->pluck('schedules.class_id')
        );

        // 3) classes whose section they are assigned to teach
        $sectionIds = DB::table('subject_teacher')
            ->where('user_id', $teacherId)
            ->whereNotNull('section_id')
            ->pluck('section_id');
        if ($sectionIds->isNotEmpty()) {
            $classIds = $classIds->merge(
                DB::table('classes')->whereIn('section_id', $sectionIds)->pluck('id')
            );
        }

        // 4) classes assigned directly via the teacher-settings "التخصيص" panel
        $classIds = $classIds->merge(
            DB::table('class_teacher')->where('teacher_id', $teacherId)->pluck('class_id')
        );

        $classIds = $classIds->filter()->unique()->values();
        if ($classIds->isEmpty()) {
            return [];
        }

        $studentIds = DB::table('users')
            ->where(function ($w) use ($classIds) {
                $w->whereIn('class_room_id', $classIds)
                    ->orWhereIn('id', DB::table('class_student')->whereIn('class_id', $classIds)->select('student_id'));
            })
            ->whereNull('deleted_at')
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->pluck('id');

        return $studentIds->map(fn ($id) => (int) $id)->all();
    }
}
