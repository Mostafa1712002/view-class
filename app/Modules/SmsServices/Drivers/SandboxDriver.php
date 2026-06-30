<?php

namespace App\Modules\SmsServices\Drivers;

use Illuminate\Support\Facades\Log;

/**
 * In-process sandbox gateway. Selected by SmsService when a school's SMS
 * service is ACTIVE but no real provider credentials are configured and
 * config('messaging.sms.sandbox') is on.
 *
 * It simulates a complete successful lifecycle synchronously — the carrier
 * accepts AND delivers the message — so the whole SMS flow (credit deduction,
 * delivery status, reports) is demonstrable end-to-end without a paid gateway.
 * No network call is made. Returning the terminal 'delivered' status (rather
 * than a separate sent→delivered write) is observationally identical here: a
 * synchronous send has no window in which an intermediate 'sent' state is
 * observable, and there is no delivered_at column to distinguish the two.
 *
 * Swapping in a real provider = entering credentials, which makes SmsService
 * pick HttpProviderDriver instead; no other code changes.
 */
final class SandboxDriver implements SmsDriverInterface
{
    public function send(string $to, string $message): array
    {
        Log::channel('daily')->info('[SMS SandboxDriver] delivered (sandbox — no real gateway)', [
            'to'      => $to,
            'message' => $message,
        ]);

        return [
            'status'            => 'delivered',
            'provider_response' => 'sandbox:delivered',
            'failure_reason'    => null,
        ];
    }
}
