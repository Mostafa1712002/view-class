<?php

namespace App\Modules\Attendance\Actions;

use App\Models\Notification;
use App\Models\School;
use App\Models\User;
use App\Modules\SmsServices\Actions\SendSmsBatchAction;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Models\SmsTemplate;
use App\Modules\SmsServices\Support\SmsTemplateRenderer;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Models\WhatsappLog;
use App\Modules\Whatsapp\Services\WhatsappService;
use App\Modules\Whatsapp\Support\PhoneNormalizer;

/**
 * Trello #271 — bridge Sprint-10 attendance screens to the existing messaging
 * layer (SMS via SendSmsBatchAction, WhatsApp via WhatsappService) + the SMS
 * message-template system. NOT a new messaging system: it composes the body
 * (from a chosen template or free text), resolves the student's notifiable
 * parents, and delegates the actual send to the channel services so message
 * rows are persisted with honest statuses (queued when no real gateway).
 *
 * Channels:
 *   in_app   → App\Models\Notification (canonical in-app record)
 *   sms      → SmsServices\SendSmsBatchAction → sms_messages (status queued)
 *   whatsapp → Whatsapp\WhatsappService       → whatsapp_logs
 *
 * @phpstan-type Outcome array{
 *     students:int, parents:int, queued:int, sent:int, failed:int, skipped:int
 * }
 */
final class SendAttendanceMessageAction
{
    public function __construct(private readonly SendSmsBatchAction $smsBatch) {}

    /**
     * @param  iterable<int, User>  $students  student users (with `parents` loaded ideally)
     * @return array{students:int,parents:int,queued:int,sent:int,failed:int,skipped:int}
     */
    public function execute(
        int $schoolId,
        iterable $students,
        string $channel,           // in_app | sms | whatsapp
        ?int $templateId,
        string $bodyTemplate,      // raw template text (may contain {placeholders})
        ?int $senderUserId = null,
        array $extraVars = [],     // event vars merged into every recipient (date/status/...)
    ): array {
        $school = School::find($schoolId);

        // Resolve the chosen SMS template body (school-scoped) when one is picked.
        if ($templateId) {
            $tpl = SmsTemplate::query()
                ->where('id', $templateId)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->first();
            if ($tpl) {
                $bodyTemplate = $tpl->body;
            } else {
                $templateId = null; // ignore a template from another school
            }
        }

        $out = ['students' => 0, 'parents' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        // For the SMS channel we batch every recipient into ONE SendSmsBatchAction
        // call (single batch = one credit reservation pass, correct reporting).
        $smsRecipients = [];

        $waSetting = $channel === 'whatsapp'
            ? SchoolWhatsappSetting::where('school_id', $schoolId)->first()
            : null;
        $waService = $channel === 'whatsapp' ? new WhatsappService($waSetting) : null;

        foreach ($students as $student) {
            $out['students']++;
            $parents = $student->parents()
                ->wherePivot('can_receive_notifications', true)
                ->get();

            foreach ($parents as $parent) {
                $out['parents']++;
                $vars = SmsTemplateRenderer::varsForUser($parent, $school, array_merge([
                    'student_name' => $student->name,
                    'parent_name'  => $parent->name,
                ], $extraVars));
                $finalBody = SmsTemplateRenderer::render($bodyTemplate, $vars, 'dash');

                match ($channel) {
                    'in_app'   => $this->inApp($parent, $student, $finalBody, $out, $extraVars),
                    'sms'      => $smsRecipients[] = [
                        'phone'   => $parent->phone,
                        'name'    => $parent->name,
                        'role'    => 'parent',
                        'user_id' => $parent->id,
                        'vars'    => $vars,
                    ],
                    'whatsapp' => $this->whatsapp($waService, $waSetting, $parent, $student, $finalBody, $schoolId, $senderUserId, $out),
                    default    => null,
                };
            }
        }

        // Drive the SMS batch once (body still contains placeholders so the
        // batch action renders per-recipient with each row's vars).
        if ($channel === 'sms' && $smsRecipients !== []) {
            $sender = SmsSender::query()
                ->where('school_id', $schoolId)->usable()->first();

            $batch = $this->smsBatch->execute(
                schoolId: $schoolId,
                senderUserId: $senderUserId,
                sender: $sender,
                templateId: $templateId,
                body: $bodyTemplate,
                recipients: $smsRecipients,
                source: 'attendance',
                name: 'رسالة حضور/غياب',
            );

            $out['queued']  += $batch->queued_count;
            $out['sent']    += $batch->sent_count;
            $out['failed']  += $batch->failed_count;
            $out['skipped'] += $batch->skipped_count;
        }

        return $out;
    }

    private function inApp(User $parent, User $student, string $body, array &$out, array $vars): void
    {
        Notification::create([
            'user_id' => $parent->id,
            'type'    => 'attendance_alert',
            'title'   => 'رسالة من المدرسة',
            'body'    => $body,
            'icon'    => 'bi-envelope',
            'color'   => 'info',
            'data'    => ['channel' => 'in_app', 'student_id' => $student->id] + $vars,
        ]);
        $out['sent']++;
    }

    private function whatsapp(
        WhatsappService $service,
        ?SchoolWhatsappSetting $setting,
        User $parent,
        User $student,
        string $body,
        int $schoolId,
        ?int $senderUserId,
        array &$out,
    ): void {
        $toNumber = PhoneNormalizer::normalize($parent->whatsapp ?? $parent->phone ?? null);
        if (! $toNumber) {
            $out['skipped']++;
            return;
        }

        // Honesty boundary: a real gateway exists only when the setting is
        // enabled AND not the 'log' stub provider. Without it we persist the
        // message as 'pending' (the enum's not-yet-delivered state) — we never
        // claim 'sent' for a stub. With a real gateway the driver's result is
        // authoritative.
        $hasGateway = $setting && $setting->is_enabled && $setting->provider !== 'log';
        $driver = $service->resolveDriver();
        $result = $driver->send($toNumber, $body);

        $status = $hasGateway
            ? ($result['success'] ? 'sent' : 'failed')
            : 'pending';

        WhatsappLog::create([
            'school_id'    => $schoolId,
            'student_id'   => $student->id,
            'parent_id'    => $parent->id,
            'recipient_user_id' => $parent->id,
            'recipient_role'    => 'parent',
            'to_number'    => $toNumber,
            'message_text' => $body,
            'message_type' => 'text',
            'status'       => $status,
            'failure_reason' => $result['failure_reason'] ?? null,
            'provider'     => $setting->provider ?? 'log',
            'sent_at'      => $status === 'sent' ? now() : null,
            'triggered_by' => $senderUserId,
            'type'         => 'attendance_message',
        ]);

        $status === 'sent' ? $out['sent']++ : $out['queued']++;
    }
}
