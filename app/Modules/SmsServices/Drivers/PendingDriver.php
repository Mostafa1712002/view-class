<?php

namespace App\Modules\SmsServices\Drivers;

use Illuminate\Support\Facades\Log;

/**
 * Fallback driver used when NO real SMS gateway is configured (the current
 * state — client has not supplied gateway credentials yet).
 *
 * It deliberately does NOT pretend the message was delivered. It logs the
 * attempt for traceability and reports status 'queued' so the message row is
 * persisted in a pending state. When real credentials are entered in the
 * connection settings, SmsService swaps this for HttpProviderDriver and the
 * exact same call path produces real 'sent'/'failed' results — no UI change.
 */
final class PendingDriver implements SmsDriverInterface
{
    public function send(string $to, string $message): array
    {
        Log::channel('daily')->info('[SMS PendingDriver] queued (no gateway configured)', [
            'to'      => $to,
            'message' => $message,
        ]);

        return [
            'status'            => 'queued',
            'provider_response' => 'queued:no-gateway',
            'failure_reason'    => null,
        ];
    }
}
