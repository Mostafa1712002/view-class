<?php

namespace App\Modules\Whatsapp\Drivers;

interface WhatsappDriverInterface
{
    /**
     * Send a WhatsApp message.
     *
     * @param  string  $to      Recipient phone number (international format)
     * @param  string  $message Message body
     * @return array{success: bool, provider_response: string|null, failure_reason: string|null}
     */
    public function send(string $to, string $message): array;
}
