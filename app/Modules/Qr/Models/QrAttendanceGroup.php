<?php

namespace App\Modules\Qr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QrAttendanceGroup extends Model
{
    use SoftDeletes;

    protected $table = 'qr_attendance_groups';

    protected $fillable = [
        'school_id', 'title', 'title_en', 'default_status',
        'present_start', 'late_start', 'absent_start', 'excuse_start',
        'work_days', 'description', 'is_active',
    ];

    protected $casts = [
        'work_days' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Resolve the status a scan at $time should produce, per the group's
     * time windows. Falls back to default_status when no windows are set.
     */
    public function statusForTime(string $time): string
    {
        // time as HH:MM:SS
        $t = strtotime($time);
        $late   = $this->late_start ? strtotime($this->late_start) : null;
        $absent = $this->absent_start ? strtotime($this->absent_start) : null;

        if ($absent !== null && $t >= $absent) {
            return 'absent';
        }
        if ($late !== null && $t >= $late) {
            return 'late';
        }

        return 'present';
    }

    public function worksOn(int $weekday): bool
    {
        if (empty($this->work_days)) {
            return true; // no restriction
        }

        return in_array($weekday, array_map('intval', $this->work_days), true);
    }
}
