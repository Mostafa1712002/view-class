<?php

namespace App\Modules\Gamification\Hooks;

use App\Models\VirtualClass;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\VirtualClasses\Models\VirtualClassAttendee;

/** teacher.live_rooms_attendance: (present + late) targeted students / targeted students. Source = the live session. */
final class VirtualClassPointHooks
{
    public function __construct(private SyncActivityPoints $sync) {}

    public function virtualClassRecalculated(VirtualClass $vc): void
    {
        $targeted = DiscussionPointHooks::targetedStudentIds($vc);
        $units = 0.0;
        if ($targeted !== []) {
            $attended = VirtualClassAttendee::where('virtual_class_id', $vc->id)
                ->whereIn('attendance_status', ['present', 'late'])->whereIn('student_id', $targeted)
                ->distinct()->count('student_id');
            $units = min(1.0, $attended / count($targeted));
        }

        $this->sync->execute(new ActivityPoints('teacher.live_rooms_attendance', (int) $vc->teacher_id, 'virtual_class', $vc->id, $units, auth()->id()));
    }
}
