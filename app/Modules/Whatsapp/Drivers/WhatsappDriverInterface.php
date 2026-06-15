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

    /**
     * Send a WhatsApp message with a media attachment (image or PDF).
     *
     * @param  string       $to        Recipient phone number (international format)
     * @param  string|null  $caption   Optional text caption
     * @param  string       $mediaUrl  Public URL / path the provider can fetch the attachment from
     * @param  string       $mediaType "image" | "pdf"
     * @return array{success: bool, provider_response: string|null, failure_reason: string|null}
     */
    public function sendMedia(string $to, ?string $caption, string $mediaUrl, string $mediaType): array;
}
