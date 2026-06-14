<?php

namespace App\Modules\Attendance\Repositories\Contracts;

use App\Models\Attendance;
use App\Models\User;

interface AttendanceRepository
{
    /**
     * Create or update an attendance record, then dispatch notification job
     * for absent/late statuses.
     */
    public function saveWithNotify(array $attendanceData, User $recorder): Attendance;

    public function findById(int $id): ?Attendance;

    /**
     * Parent submits an excuse for an existing absence/late record.
     */
    public function submitExcuse(Attendance $attendance, string $excuseText, User $parent): Attendance;

    /**
     * Admin/teacher accepts or rejects a parent excuse.
     *
     * @param  string  $decision  'accepted' or 'rejected'
     */
    public function reviewExcuse(Attendance $attendance, string $decision, User $reviewer): Attendance;
}
