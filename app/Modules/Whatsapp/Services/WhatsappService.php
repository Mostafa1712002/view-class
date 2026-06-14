<?php

namespace App\Modules\Whatsapp\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Modules\Whatsapp\Drivers\HttpProviderDriver;
use App\Modules\Whatsapp\Drivers\LogDriver;
use App\Modules\Whatsapp\Drivers\WhatsappDriverInterface;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Models\WhatsappLog;
use App\Modules\Whatsapp\Repositories\Contracts\WhatsappSettingsRepository;

class WhatsappService
{
    public function __construct(
        private readonly ?SchoolWhatsappSetting $setting,
    ) {}

    public function resolveDriver(): WhatsappDriverInterface
    {
        if ($this->setting?->provider === 'log' || $this->setting === null) {
            return new LogDriver();
        }

        return new HttpProviderDriver(
            $this->setting->api_url,
            $this->setting->api_token,
        );
    }

    public function renderTemplate(string $template, array $vars): string
    {
        $placeholders = [
            '{student_name}' => $vars['student_name'] ?? '',
            '{date}'         => $vars['date'] ?? '',
            '{absence_type}' => $vars['absence_type'] ?? '',
            '{subject_name}' => $vars['subject_name'] ?? '',
            '{period_name}'  => $vars['period_name'] ?? '',
            '{school_name}'  => $vars['school_name'] ?? '',
            '{brand}'        => $vars['brand'] ?? 'المنصة الذهبية',
        ];

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template
        );
    }

    public function defaultTemplate(string $type): string
    {
        return match ($type) {
            'absence' => 'عزيزي ولي الأمر، نُعلمكم بأن الطالب/ة {student_name} كان/ت غائباً/ة {absence_type} بتاريخ {date}. للاستفسار تواصل مع {school_name}. {brand}',
            'late'    => 'عزيزي ولي الأمر، نُعلمكم بأن الطالب/ة {student_name} تأخر/ت عن موعد الدوام بتاريخ {date}. {school_name}. {brand}',
            'excuse_accepted' => 'عزيزي ولي الأمر، تم قبول عذر الطالب/ة {student_name} للغياب بتاريخ {date}. {school_name}. {brand}',
            'excuse_rejected' => 'عزيزي ولي الأمر، لم يتم قبول عذر الطالب/ة {student_name} للغياب بتاريخ {date}. للاستفسار تواصل مع {school_name}. {brand}',
            default   => 'إشعار من {school_name} بخصوص الطالب/ة {student_name}. {brand}',
        };
    }

    /**
     * Send absence/late alert to a parent via WhatsApp.
     * Returns null when sending is skipped (disabled, no number, duplicate, etc.).
     */
    public function sendAbsenceAlert(
        Attendance $attendance,
        User $parent,
        string $type,
    ): ?WhatsappLog {
        // Guard: no setting or not enabled
        if ($this->setting === null || !$this->setting->is_enabled) {
            return null;
        }

        // Guard: toggle per type
        $toggleMap = [
            'absence' => $attendance->period
                ? 'send_on_period_absence'
                : 'send_on_day_absence',
            'late'            => 'send_on_late',
            'excuse_accepted' => 'send_on_excuse_accepted',
            'excuse_rejected' => 'send_on_excuse_rejected',
        ];

        $toggle = $toggleMap[$type] ?? null;
        if ($toggle && !$this->setting->{$toggle}) {
            return null;
        }

        // Resolve recipient number
        $toNumber = $parent->whatsapp ?? $parent->phone ?? null;
        if (empty($toNumber)) {
            return null;
        }

        // Guard: no duplicate for same attendance + parent + type
        $existing = WhatsappLog::where('attendance_id', $attendance->id)
            ->where('parent_id', $parent->id)
            ->where('type', $type)
            ->whereIn('status', ['sent', 'pending'])
            ->first();

        if ($existing) {
            return null;
        }

        // Build template vars
        $student    = $attendance->student;
        $subject    = $attendance->subject;
        $school     = $student?->school ?? null;
        $absenceType = $attendance->period ? 'حصة' : 'يومي';

        $templateSource = match ($type) {
            'absence'         => $this->setting->template_absence,
            'late'            => $this->setting->template_late,
            'excuse_accepted' => $this->setting->template_excuse_accepted,
            'excuse_rejected' => $this->setting->template_excuse_rejected,
            default           => null,
        } ?? $this->defaultTemplate($type);

        $message = $this->renderTemplate($templateSource, [
            'student_name' => $student?->name ?? '',
            'date'         => $attendance->date?->format('Y-m-d') ?? '',
            'absence_type' => $absenceType,
            'subject_name' => $subject?->name ?? '',
            'period_name'  => $attendance->period ? ('الحصة ' . $attendance->period) : '',
            'school_name'  => $school?->name ?? '',
            'brand'        => 'المنصة الذهبية',
        ]);

        $driver = $this->resolveDriver();
        $result = $driver->send($toNumber, $message);

        $log = WhatsappLog::create([
            'school_id'    => $student?->school_id,
            'student_id'   => $student?->id,
            'parent_id'    => $parent->id,
            'attendance_id' => $attendance->id,
            'to_number'    => $toNumber,
            'message_text' => $message,
            'status'       => $result['success'] ? 'sent' : 'failed',
            'failure_reason' => $result['failure_reason'],
            'provider'     => $this->setting->provider,
            'sent_at'      => $result['success'] ? now() : null,
            'type'         => $type,
        ]);

        return $log;
    }
}
