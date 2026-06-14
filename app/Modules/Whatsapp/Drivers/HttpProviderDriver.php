<?php

namespace App\Modules\Whatsapp\Drivers;

use Illuminate\Support\Facades\Http;
use Throwable;

class HttpProviderDriver implements WhatsappDriverInterface
{
    public function __construct(
        private readonly ?string $apiUrl,
        private readonly ?string $apiToken,
    ) {}

    public function send(string $to, string $message): array
    {
        if (empty($this->apiUrl)) {
            return [
                'success'           => false,
                'provider_response' => null,
                'failure_reason'    => 'provider not configured',
            ];
        }

        try {
            $response = Http::timeout(15)->post($this->apiUrl, [
                'to'      => $to,
                'message' => $message,
                'token'   => $this->apiToken,
            ]);

            if ($response->successful()) {
                return [
                    'success'           => true,
                    'provider_response' => $response->body(),
                    'failure_reason'    => null,
                ];
            }

            return [
                'success'           => false,
                'provider_response' => $response->body(),
                'failure_reason'    => 'HTTP ' . $response->status(),
            ];
        } catch (Throwable $e) {
            return [
                'success'           => false,
                'provider_response' => null,
                'failure_reason'    => $e->getMessage(),
            ];
        }
    }
}
