<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\School;
use App\Modules\Attendance\Actions\SendAttendanceMessageAction;
use App\Modules\SmsServices\Models\SmsAutoMessageSetting;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Models\SmsTemplate;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use Illuminate\Console\Command;

/**
 * إدارة رسائل غياب الطالب — auto mode. Runs hourly; for every school with
 * absent_daily enabled whose local time has passed its configured send_time,
 * messages the parents of every student still absent (daily row, period IS
 * NULL) today that hasn't been notified yet. Dedupe is `notified_parent`
 * (set on every attempted row) — a school already fully processed today has
 * nothing left un-notified, so a second run within the hour is a no-op.
 */
class SendDailyAbsenceMessages extends Command
{
    private const TEMPLATE_TITLE = 'رسالة غياب الطالب اليومي';

    protected $signature = 'attendance:send-daily-absence-messages';

    protected $description = 'Send the saved absence message to parents of students absent today, after each school\'s configured end-of-day time';

    public function handle(SendAttendanceMessageAction $sender): int
    {
        $settings = SmsAutoMessageSetting::query()
            ->where('event_type', 'absent_daily')
            ->where('is_enabled', true)
            ->whereNotNull('send_time')
            ->get()
            ->keyBy('school_id');

        if ($settings->isEmpty()) {
            $this->info('No school has auto absence messages enabled.');

            return self::SUCCESS;
        }

        $schools = School::query()->whereIn('id', $settings->keys())->get();
        $totalSent = 0;

        foreach ($schools as $school) {
            $setting = $settings->get($school->id);
            $now = now($school->timezone ?: config('app.timezone'));

            if ($now->format('H:i:s') < (string) $setting->send_time) {
                continue; // school day hasn't ended yet in its own timezone
            }

            $today = $now->toDateString();

            $rows = Attendance::query()->absent()->whereNull('period')->whereDate('date', $today)
                ->where('notified_parent', false)
                ->whereHas('classRoom.section', fn ($q) => $q->where('school_id', $school->id))
                ->with(['student.parents', 'classRoom'])
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $body = SmsTemplate::query()->where('school_id', $school->id)->where('title', self::TEMPLATE_TITLE)->value('body')
                ?? $setting->template_body
                ?? SmsAutoMessageSetting::EVENT_TYPES['absent_daily']['default'];
            $channel = $this->resolveChannel($school->id);

            foreach ($rows->groupBy('class_id') as $classRows) {
                $classRoom = $classRows->first()->classRoom;
                $students = $classRows->pluck('student')->filter()->unique('id')->values();

                $r = $sender->execute(
                    schoolId: $school->id,
                    students: $students,
                    channel: $channel,
                    templateId: null,
                    bodyTemplate: $body,
                    senderUserId: null,
                    extraVars: [
                        'date'             => $today,
                        'attendance_state' => 'غائب',
                        'class'            => $classRoom?->name ?? '',
                        'grade'            => $this->gradeLabel((int) ($classRoom?->grade_level ?? 0)),
                    ],
                );
                $totalSent += $r['sent'] + $r['queued'];
            }

            Attendance::whereIn('id', $rows->pluck('id'))->update(['notified_parent' => true]);
        }

        $this->info("Auto absence messages processed. Sent/queued: {$totalSent}.");

        return self::SUCCESS;
    }

    private function resolveChannel(int $schoolId): string
    {
        if (SchoolWhatsappSetting::where('school_id', $schoolId)->value('is_enabled')) {
            return 'whatsapp';
        }
        if (SmsSender::query()->where('school_id', $schoolId)->usable()->exists()) {
            return 'sms';
        }

        return 'in_app';
    }

    private function gradeLabel(int $grade): string
    {
        $names = [1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الخامس', 6 => 'السادس',
            7 => 'السابع', 8 => 'الثامن', 9 => 'التاسع', 10 => 'العاشر', 11 => 'الحادي عشر', 12 => 'الثاني عشر'];

        return $names[$grade] ?? "الصف {$grade}";
    }
}
