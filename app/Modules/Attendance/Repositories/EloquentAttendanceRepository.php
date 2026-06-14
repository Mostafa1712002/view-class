<?php

namespace App\Modules\Attendance\Repositories;

use App\Models\Attendance;
use App\Models\User;
use App\Modules\Attendance\Jobs\NotifyAbsenceJob;
use App\Modules\Attendance\Repositories\Contracts\AttendanceRepository;

class EloquentAttendanceRepository implements AttendanceRepository
{
    public function saveWithNotify(array $attendanceData, User $recorder): Attendance
    {
        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $attendanceData['student_id'],
                'class_id'   => $attendanceData['class_id'],
                'date'       => $attendanceData['date'],
                'period'     => $attendanceData['period'] ?? null,
            ],
            array_merge($attendanceData, ['teacher_id' => $recorder->id])
        );

        if (in_array($attendance->status, ['absent', 'late'], true)) {
            dispatch(new NotifyAbsenceJob($attendance->id));
        }

        return $attendance;
    }

    public function findById(int $id): ?Attendance
    {
        return Attendance::find($id);
    }

    public function submitExcuse(Attendance $attendance, string $excuseText, User $parent): Attendance
    {
        $attendance->update([
            'excuse_status'       => 'pending',
            'excuse_text'         => $excuseText,
            'excuse_submitted_at' => now(),
        ]);

        return $attendance->refresh();
    }

    public function reviewExcuse(Attendance $attendance, string $decision, User $reviewer): Attendance
    {
        $attendance->update([
            'excuse_status'       => $decision,
            'excuse_reviewed_at'  => now(),
            'excuse_reviewed_by'  => $reviewer->id,
        ]);

        return $attendance->refresh();
    }
}
