<?php

namespace App\Modules\Attendance\Jobs;

use App\Models\Attendance;
use App\Models\Notification;
use App\Modules\SmsServices\Actions\SendSmsBatchAction;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Support\SmsTemplateRenderer;
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

        // 3. SMS channel (#271) — queue one batch to all notifiable parents
        // through the real SMS layer. PendingDriver parks it as 'queued' when
        // no gateway is configured; SendSmsBatchAction handles credit/skip.
        try {
            $this->queueSms($student, $attendance, $type, $parents);
        } catch (Throwable $e) {
            Log::error('[NotifyAbsenceJob] SMS queue failed', [
                'attendance_id' => $attendance->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build SMS recipients from notifiable parents and hand them to the SMS
     * batch action (real messaging layer, honest 'queued' status).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\User>  $parents
     */
    private function queueSms(\App\Models\User $student, Attendance $attendance, string $type, $parents): void
    {
        $schoolId = (int) ($student->school_id ?? 0);
        if ($schoolId === 0) {
            return;
        }

        $school = $student->school;
        $body = $type === 'late'
            ? 'عزيزي ولي الأمر، تأخر الطالب/ة {student_name} عن الحضور بتاريخ {date}. {school_name}'
            : 'عزيزي ولي الأمر، نُعلمكم بغياب الطالب/ة {student_name} بتاريخ {date}. {school_name}';

        $date = $attendance->date?->format('Y-m-d') ?? now()->format('Y-m-d');

        $recipients = [];
        foreach ($parents as $parent) {
            $recipients[] = [
                'phone'   => $parent->phone,
                'name'    => $parent->name,
                'role'    => 'parent',
                'user_id' => $parent->id,
                'vars'    => SmsTemplateRenderer::varsForUser($parent, $school, [
                    'student_name' => $student->name,
                    'parent_name'  => $parent->name,
                    'date'         => $date,
                ]),
            ];
        }

        if ($recipients === []) {
            return;
        }

        $sender = SmsSender::query()->where('school_id', $schoolId)->usable()->first();

        app(SendSmsBatchAction::class)->execute(
            schoolId: $schoolId,
            senderUserId: null,
            sender: $sender,
            templateId: null,
            body: $body,
            recipients: $recipients,
            source: 'attendance',
            name: $type === 'late' ? 'تنبيه تأخير تلقائي' : 'تنبيه غياب تلقائي',
        );
    }
}
