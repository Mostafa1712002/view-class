<?php

namespace App\Modules\SmsServices\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Real HTTP gateway driver. Activated automatically by SmsService once the
 * school's connection settings carry both api_key and api_secret.
 *
 * The exact request shape depends on the provider the client picks; this is a
 * generic JSON POST that we will adapt to the chosen gateway's spec the moment
 * credentials + endpoint are supplied. Until then it is never instantiated
 * (SmsService falls back to PendingDriver), so it cannot fabricate a success.
 */
final class HttpProviderDriver implements SmsDriverInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly ?string $sender = null,
        private readonly ?string $endpoint = null,
    ) {}

    public function send(string $to, string $message): array
    {
        // No endpoint configured → cannot really send; stay honest, keep queued.
        if (empty($this->endpoint)) {
            return [
                'status'            => 'queued',
                'provider_response' => 'queued:no-endpoint',
                'failure_reason'    => null,
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => "Bearer {$this->apiKey}"])
                ->post($this->endpoint, [
                    'api_secret' => $this->apiSecret,
                    'sender'     => $this->sender,
                    'to'         => $to,
                    'message'    => $message,
                ]);

            if ($response->successful()) {
                return [
                    'status'            => 'sent',
                    'provider_response' => $response->body(),
                    'failure_reason'    => null,
                ];
            }

            return [
                'status'            => 'failed',
                'provider_response' => $response->body(),
                'failure_reason'    => 'gateway_rejected',
            ];
        } catch (Throwable $e) {
            Log::channel('daily')->error('[SMS HttpProviderDriver] send failed', [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'status'            => 'failed',
                'provider_response' => null,
                'failure_reason'    => 'gateway_exception',
            ];
        }
    }
}
