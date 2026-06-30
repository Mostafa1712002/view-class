<?php

namespace App\Modules\SmsServices\Actions;

use App\Modules\SmsServices\Models\SchoolSmsSetting;
use App\Modules\SmsServices\Models\SmsBatch;
use App\Modules\SmsServices\Models\SmsMessage;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Services\CreditService;
use App\Modules\SmsServices\Services\SmsService;
use App\Modules\SmsServices\Support\SmsSegmentCalculator;
use App\Modules\SmsServices\Support\SmsTemplateRenderer;
use App\Modules\Whatsapp\Support\PhoneNormalizer;

/**
 * Persists an SMS send batch + one message row per recipient with honest
 * statuses, deducts credit per actual segment count, and drives the configured
 * SMS driver (Trello #239).
 *
 * Status contract: the driver decides the outcome. A real gateway returns
 * 'sent'/'failed'; the sandbox (active school, no real gateway) returns
 * 'delivered'; with no gateway and no sandbox the driver parks it as 'queued'.
 * Credit is reserved per segment on every non-failed outcome; a hard 'failed'
 * is refunded (it never reached the carrier).
 */
final class SendSmsBatchAction
{
    public function __construct(private readonly CreditService $credit) {}

    /**
     * @param  array<int, array{
     *     phone:?string, name:?string, role:?string, user_id:?int, vars:array<string,mixed>
     * }>  $recipients  raw recipient rows
     */
    public function execute(
        int $schoolId,
        ?int $senderUserId,
        ?SmsSender $sender,
        ?int $templateId,
        string $body,
        array $recipients,
        string $source = 'compose',
        ?string $name = null,
        string $onMissing = 'blank',
    ): SmsBatch {
        $setting = SchoolSmsSetting::firstOrCreate(
            ['school_id' => $schoolId],
            ['is_active' => false, 'sms_used' => 0, 'sms_total' => 0, 'provider' => 'generic']
        );

        $service  = new SmsService($setting);
        $driver   = $service->resolveDriver();
        $provider = $service->provider();

        $batch = SmsBatch::create([
            'school_id'            => $schoolId,
            'sender_user_id'       => $senderUserId,
            'sender_id'            => $sender?->id,
            'sender_name_snapshot' => $sender?->name_en ?? $sender?->name_ar,
            'template_id'          => $templateId,
            'name'                 => $name,
            'source'               => $source,
            'provider'             => $provider,
            'status'               => 'queued',
        ]);

        $sent = $failed = $queued = $skipped = 0;
        $totalSegments = 0;
        $totalCredit   = 0;
        $seen = [];

        foreach ($recipients as $r) {
            $rawPhone = $r['phone'] ?? null;
            $normalized = PhoneNormalizer::normalize($rawPhone);
            $finalBody  = SmsTemplateRenderer::render($body, $r['vars'] ?? [], $onMissing);
            $segments   = max(1, SmsSegmentCalculator::segments($finalBody));

            // --- skip rules (never call the gateway for a bad recipient) ---
            if ($normalized === null) {
                $this->row($batch, $schoolId, $templateId, $sender, $r, '', $finalBody, 'no_number', 0, $segments, $senderUserId, $provider);
                $skipped++;
                continue;
            }
            if (! PhoneNormalizer::isValid($normalized)) {
                $this->row($batch, $schoolId, $templateId, $sender, $r, $normalized, $finalBody, 'invalid_number', 0, $segments, $senderUserId, $provider);
                $skipped++;
                continue;
            }
            if (isset($seen[$normalized])) {
                $this->row($batch, $schoolId, $templateId, $sender, $r, $normalized, $finalBody, 'rejected', 0, $segments, $senderUserId, $provider, 'رقم مكرر');
                $skipped++;
                continue;
            }
            $seen[$normalized] = true;

            // --- credit gate: reserve per actual segments ---
            $ledger = $this->credit->deduct(
                $setting,
                $segments,
                'إرسال SMS — دفعة #' . $batch->id,
                $senderUserId,
                'sms_batches',
                $batch->id
            );

            if ($ledger === null) {
                // insufficient credit — persist as no_credit, do NOT send
                $this->row($batch, $schoolId, $templateId, $sender, $r, $normalized, $finalBody, 'no_credit', 0, $segments, $senderUserId, $provider);
                $failed++;
                continue;
            }

            // --- drive the actual send (delivered in sandbox, queued when no gateway) ---
            $result = $driver->send($normalized, $finalBody);
            $status = $result['status']; // queued | sent | delivered | failed
            $succeeded = in_array($status, ['sent', 'delivered'], true);

            // refund if the gateway hard-failed (it never reached the carrier)
            if ($status === 'failed') {
                $this->credit->recharge($setting, $segments, 'استرجاع رصيد لرسالة فاشلة — دفعة #' . $batch->id, $senderUserId, 'sms_batches', $batch->id);
                $charged = 0;
            } else {
                $charged = $segments;
                $totalCredit += $segments;
            }

            $this->row(
                $batch, $schoolId, $templateId, $sender, $r, $normalized, $finalBody,
                $status, $charged, $segments, $senderUserId, $provider,
                $result['failure_reason'] ?? null,
                $succeeded
            );

            $totalSegments += $segments;
            if ($status === 'failed') {
                $failed++;
            } elseif ($status === 'queued') {
                $queued++;
            } else { // sent | delivered
                $sent++;
            }
        }

        $batch->update([
            'total_recipients' => $sent + $failed + $queued + $skipped,
            'total_messages'   => $totalSegments,
            'sent_count'       => $sent,
            'failed_count'     => $failed,
            'queued_count'     => $queued,
            'skipped_count'    => $skipped,
            'credit_charged'   => $totalCredit,
            'status'           => $this->batchStatus($sent, $failed, $queued),
            'sent_at'          => now(),
        ]);

        return $batch->refresh();
    }

    private function row(
        SmsBatch $batch,
        int $schoolId,
        ?int $templateId,
        ?SmsSender $sender,
        array $r,
        string $number,
        string $body,
        string $status,
        int $credit,
        int $segments,
        ?int $triggeredBy,
        string $provider,
        ?string $error = null,
        bool $sentNow = false
    ): void {
        SmsMessage::create([
            'school_id'         => $schoolId,
            'batch_id'          => $batch->id,
            'template_id'       => $templateId,
            'sender_id'         => $sender?->id,
            'recipient_user_id' => $r['user_id'] ?? null,
            'recipient'         => $number,
            'recipient_name'    => $r['name'] ?? null,
            'recipient_role'    => $r['role'] ?? null,
            'body'              => $body,
            'status'            => $status,
            'provider'          => $provider,
            'channel'           => 'sms',
            'message_count'     => $segments,
            'credit_charged'    => $credit,
            'triggered_by'      => $triggeredBy,
            'error'             => $error,
            'sent_at'           => $sentNow ? now() : null,
        ]);
    }

    private function batchStatus(int $sent, int $failed, int $queued): string
    {
        if ($queued > 0 && $sent === 0 && $failed === 0) {
            return 'queued';
        }
        if ($failed > 0 && $sent === 0 && $queued === 0) {
            return 'failed';
        }
        if ($sent > 0 && $failed === 0 && $queued === 0) {
            return 'sent';
        }

        return 'partial';
    }
}
