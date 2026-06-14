<?php

namespace App\Modules\Attendance\Jobs;

use App\Models\Attendance;
use App\Models\Notification;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyAbsenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $attendanceId,
    ) {}

    public function handle(): void
    {
        $attendance = Attendance::with(['student', 'teacher', 'subject', 'classRoom'])
            ->find($this->attendanceId);

        if (!$attendance) {
            Log::warning('[NotifyAbsenceJob] Attendance not found', ['id' => $this->attendanceId]);
            return;
        }

        $student = $attendance->student;
        if (!$student) {
            return;
        }

        $type = match ($attendance->status) {
            'absent' => 'absence',
            'late'   => 'late',
            default  => null,
        };

        if ($type === null) {
            return;
        }

        $parents = $student->parents()
            ->wherePivot('can_receive_notifications', true)
            ->get();

        if ($parents->isEmpty()) {
            return;
        }

        // Resolve WhatsApp setting once per job
        $setting = SchoolWhatsappSetting::where('school_id', $student->school_id)->first();
        $whatsappService = new WhatsappService($setting);

        $statusLabel = $attendance->status === 'absent' ? 'غائب' : 'متأخر';
        $dateFormatted = $attendance->date?->format('Y-m-d') ?? now()->format('Y-m-d');

        foreach ($parents as $parent) {
            // 1. In-app notification
            try {
                Notification::create([
                    'user_id'     => $parent->id,
                    'type'        => 'attendance_alert',
                    'title'       => 'تنبيه حضور',
                    'body'        => "الطالب/ة {$student->name} {$statusLabel} بتاريخ {$dateFormatted}",
                    'icon'        => 'bi-exclamation-triangle',
                    'color'       => $attendance->status === 'absent' ? 'danger' : 'warning',
                    'action_url'  => route('parent.child.attendance', $student),
                    'action_text' => 'عرض سجل الحضور',
                    'data'        => [
                        'attendance_id' => $attendance->id,
                        'status'        => $attendance->status,
                        'date'          => $dateFormatted,
                    ],
                ]);
            } catch (Throwable $e) {
                Log::error('[NotifyAbsenceJob] Failed to create in-app notification', [
                    'parent_id'    => $parent->id,
                    'attendance_id' => $attendance->id,
                    'error'        => $e->getMessage(),
                ]);
            }

            // 2. WhatsApp notification
            try {
                $whatsappService->sendAbsenceAlert($attendance, $parent, $type);
            } catch (Throwable $e) {
                Log::error('[NotifyAbsenceJob] WhatsApp send failed', [
                    'parent_id'    => $parent->id,
                    'attendance_id' => $attendance->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }
    }
}
