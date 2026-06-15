<?php

namespace App\Modules\Whatsapp\Drivers;

use Illuminate\Support\Facades\Log;

class LogDriver implements WhatsappDriverInterface
{
    public function send(string $to, string $message): array
    {
        Log::channel('daily')->info('[WhatsApp Log Driver]', [
            'to'      => $to,
            'message' => $message,
        ]);

        return [
            'success'           => true,
            'provider_response' => 'logged',
            'failure_reason'    => null,
        ];
    }

    public function sendMedia(string $to, ?string $caption, string $mediaUrl, string $mediaType): array
    {
        Log::channel('daily')->info('[WhatsApp Log Driver — media]', [
            'to'         => $to,
            'caption'    => $caption,
            'media_url'  => $mediaUrl,
            'media_type' => $mediaType,
        ]);

        return [
            'success'           => true,
            'provider_response' => 'logged',
            'failure_reason'    => null,
        ];
    }
}
