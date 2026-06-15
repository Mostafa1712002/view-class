<?php

namespace App\Modules\VirtualClasses\Actions;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\VirtualClass;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Recalculate virtual-class attendance.
 *
 * Reads the entry/exit log (virtual_class_attendees), computes each student's
 * presence duration, derives a status, and mirrors the result into the SHARED
 * `attendances` table — keyed so it is idempotent — so existing parent/student
 * attendance reports surface virtual-class attendance with no changes to them.
 *
 * Status rules (share-of-session attended):
 *   - present  : >= 80% of duration   (or joined late but stayed most of it)
 *   - late     : present but joined after the first 15 min, attended >= 50%
 *   - partial  : 20%–50% attended
 *   - absent   : never joined OR < 20% attended
 */
final class RecalcAttendanceAction
{
    public function __construct(private VirtualClassRepositoryInterface $repo) {}

    /**
     * @return array{present:int,late:int,partial:int,absent:int,total:int}
     */
    public function execute(VirtualClass $vc): array
    {
        $academicYearId = optional(
            AcademicYear::where('school_id', $vc->school_id)->where('is_current', true)->first()
                ?? AcademicYear::where('is_current', true)->first()
        )->id;

        // Without an academic year or a class roster we cannot write report-bound
        // attendance rows (NOT NULL FKs). Recalc still updates the in-module log.
        $roster   = $this->repo->rosterStudentIds($vc->class_id);
        $entries  = $this->repo->attendeesFor($vc->id)->keyBy('student_id');
        $duration = max(1, (int) $vc->duration_minutes);

        $summary = ['present' => 0, 'late' => 0, 'partial' => 0, 'absent' => 0, 'total' => 0];

        // The expected roster = enrolled students of the class. If the session is
        // school-wide (no class_id) we can only evaluate students who actually
        // joined (no absent inference possible).
        $studentIds = ! empty($roster)
            ? $roster
            : $entries->keys()->map(fn ($v) => (int) $v)->all();

        $canWriteShared = $academicYearId && $vc->class_id;
        $date           = $vc->scheduled_at->toDateString();

        DB::transaction(function () use ($studentIds, $entries, $vc, $duration, $academicYearId, $canWriteShared, $date, &$summary) {
            foreach ($studentIds as $studentId) {
                $studentId = (int) $studentId;
                $entry     = $entries->get($studentId);

                $status   = 'absent';
                $minutes  = 0;
                $arrival  = null;

                if ($entry && $entry->joined_at) {
                    $end     = $entry->left_at ?: $vc->scheduled_at->copy()->addMinutes($duration);
                    $minutes = max(0, $entry->joined_at->diffInMinutes($end));
                    $minutes = min($minutes, $duration);
                    $share   = $minutes / $duration;
                    $arrival = $entry->joined_at->format('H:i:s');

                    $joinedLate = $entry->joined_at->gt($vc->scheduled_at->copy()->addMinutes(15));

                    if ($share >= 0.5) {
                        $status = $joinedLate ? 'late' : 'present';
                    } elseif ($share >= 0.2) {
                        $status = 'partial';
                    } else {
                        $status = 'absent';
                    }

                    // Persist the FULL 4-state truth onto the log row — the in-module
                    // attendance view renders `partial` with its own colour.
                    $entry->duration_minutes  = $minutes;
                    $entry->attendance_status = $status;
                    $entry->save();
                }

                $summary[$status] = ($summary[$status] ?? 0) + 1;
                $summary['total']++;

                if (! $canWriteShared) {
                    continue;
                }

                // Mirror into the shared attendances table using ONLY the legacy
                // 4-status vocabulary the existing parent/student reports understand
                // (present/absent/late/excused). `partial` is a virtual-class concept;
                // mapping it to `late` keeps report stat breakdowns and labels correct
                // (we must not edit the shared report code, which has no `partial`).
                // The full `partial` truth stays in virtual_class_attendees.
                $sharedStatus = $status === 'partial' ? 'late' : $status;

                // Idempotent upsert. Stable key: student + class + date + a virtual-class
                // note tag (period = NULL) so repeated recalc updates the same row.
                Attendance::updateOrCreate(
                    [
                        'student_id'       => $studentId,
                        'class_id'         => $vc->class_id,
                        'date'             => $date,
                        'subject_id'       => $vc->subject_id,
                        'academic_year_id' => $academicYearId,
                        'notes'            => 'virtual_class:' . $vc->id,
                    ],
                    [
                        'teacher_id'      => $vc->teacher_id,
                        'period'          => null,
                        'status'          => $sharedStatus,
                        'arrival_time'    => $arrival,
                        // The row is visible in the parent/student report; we do not
                        // push an active alert here (NotifyAbsenceJob is the alert path
                        // and is dispatched only by the daily-attendance flow).
                        'notified_parent' => false,
                    ]
                );
            }
        });

        // Cache the latest summary so the attendance view + clear_cache button
        // operate on a real key.
        Cache::put($this->summaryCacheKey($vc->id), $summary, now()->addDay());

        ActivityLog::log(
            'recalc_attendance',
            "إعادة احتساب حضور الفصل الافتراضي: {$vc->title}",
            $vc,
            null,
            $summary
        );

        return $summary;
    }

    public function summaryCacheKey(int $vcId): string
    {
        return "vc_attendance_summary_{$vcId}";
    }
}
