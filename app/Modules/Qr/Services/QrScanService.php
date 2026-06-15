<?php

namespace App\Modules\Qr\Services;

use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Modules\Qr\Models\QrCard;
use App\Modules\Qr\Models\QrScan;
use Carbon\Carbon;

/**
 * Validates a scanned token and records the resulting attendance + scan log.
 * Returns a result array consumed by the controller.
 */
class QrScanService
{
    /**
     * @return array{ok:bool, status:string, message:string, error_code:?string, student:?\App\Models\User}
     */
    public function record(string $token, ?int $schoolId, array $opts = []): array
    {
        $channel    = $opts['channel'] ?? 'manual';
        $deviceName = $opts['device_name'] ?? null;
        $scanTime   = ! empty($opts['scan_time']) ? Carbon::parse($opts['scan_time']) : now();
        $recordedBy = $opts['recorded_by'] ?? null;

        $card = QrCard::with(['student', 'group'])->where('token', $token)->first();

        // --- validation chain (error states from the card spec) ---
        if (! $card) {
            return $this->fail('rejected', 'QR غير صالح.', 'invalid_qr');
        }
        if (! $card->is_active) {
            return $this->logFail($card, $schoolId, $scanTime, $channel, $deviceName, $recordedBy, 'البطاقة معطلة.', 'card_disabled');
        }
        if ($card->expires_at && $card->expires_at->isPast()) {
            return $this->logFail($card, $schoolId, $scanTime, $channel, $deviceName, $recordedBy, 'انتهت صلاحية البطاقة.', 'card_expired');
        }
        $student = $card->student;
        if (! $student) {
            return $this->fail('rejected', 'الطالب غير موجود.', 'no_student');
        }
        // scope: a school-bound scanner only accepts its own students
        if ($schoolId !== null && (int) $student->school_id !== $schoolId) {
            return $this->logFail($card, $schoolId, $scanTime, $channel, $deviceName, $recordedBy, 'الطالب غير مرتبط بالمدرسة.', 'wrong_school');
        }

        $date = $scanTime->toDateString();

        // already scanned today?
        $dup = QrScan::where('student_id', $student->id)
            ->whereDate('scan_date', $date)
            ->where('result_status', '!=', 'rejected')
            ->exists();
        if ($dup) {
            return $this->logFail($card, $schoolId, $scanTime, $channel, $deviceName, $recordedBy, 'تم تسجيل حضور الطالب مسبقاً اليوم.', 'already_scanned');
        }

        // day closed for this student's class?
        if ($this->isDayClosed((int) $student->school_id, $student->class_room_id, $date)) {
            return $this->logFail($card, $schoolId, $scanTime, $channel, $deviceName, $recordedBy, 'تم إغلاق اليوم لهذا الفصل.', 'day_closed');
        }

        // derive status from the group's time windows (default present)
        $group = $card->group;
        if ($group) {
            if (! $group->is_active || ! $group->worksOn((int) $scanTime->dayOfWeek)) {
                return $this->logFail($card, $schoolId, $scanTime, $channel, $deviceName, $recordedBy, 'لا توجد مجموعة حضور نشطة لهذا اليوم.', 'no_active_group');
            }
            $status = $group->statusForTime($scanTime->format('H:i:s'));
        } else {
            $status = 'present';
        }

        // record the scan
        $scan = QrScan::create([
            'qr_card_id'    => $card->id,
            'student_id'    => $student->id,
            'school_id'     => $student->school_id,
            'group_id'      => $card->group_id,
            'scan_date'     => $date,
            'scanned_at'    => $scanTime,
            'result_status' => $status,
            'channel'       => $channel,
            'device_name'   => $deviceName,
            'recorded_by'   => $recordedBy,
        ]);

        // mirror into the canonical attendances table (daily record)
        if ($student->class_room_id) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'class_id'   => $student->class_room_id,
                    'date'       => $date,
                    'period'     => null,
                ],
                [
                    'teacher_id'       => $recordedBy ?? $student->id,
                    'academic_year_id' => AcademicYear::where('is_current', true)->value('id'),
                    'status'           => $status === 'rejected' ? 'absent' : $status,
                    'arrival_time'     => $scanTime->format('H:i'),
                ]
            );
        }

        $labels = ['present' => 'حاضر', 'late' => 'متأخر', 'absent' => 'غائب', 'excused' => 'مستأذن'];

        return [
            'ok'         => true,
            'status'     => $status,
            'message'    => "تم تسجيل {$student->name} — {$labels[$status]}",
            'error_code' => null,
            'student'    => $student,
            'scan'       => $scan,
        ];
    }

    public function isDayClosed(?int $schoolId, ?int $classId, string $date): bool
    {
        return \App\Modules\Qr\Models\QrDayClosure::query()
            ->where('close_date', $date)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->where(function ($q) use ($classId) {
                $q->whereNull('class_id');
                if ($classId) {
                    $q->orWhere('class_id', $classId);
                }
            })
            ->exists();
    }

    private function fail(string $status, string $message, string $code): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message, 'error_code' => $code, 'student' => null];
    }

    private function logFail(QrCard $card, ?int $schoolId, Carbon $scanTime, string $channel, ?string $device, ?int $recordedBy, string $message, string $code): array
    {
        QrScan::create([
            'qr_card_id'    => $card->id,
            'student_id'    => $card->student_id,
            'school_id'     => $card->school_id,
            'group_id'      => $card->group_id,
            'scan_date'     => $scanTime->toDateString(),
            'scanned_at'    => $scanTime,
            'result_status' => 'rejected',
            'channel'       => $channel,
            'device_name'   => $device,
            'error_code'    => $code,
            'recorded_by'   => $recordedBy,
        ]);

        return ['ok' => false, 'status' => 'rejected', 'message' => $message, 'error_code' => $code, 'student' => $card->student];
    }
}
