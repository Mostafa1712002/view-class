<?php

namespace App\Modules\Whatsapp\DTOs;

final class ComposeMessageDto
{
    /**
     * @param  array<int>  $recipientIds  Final, user-selected recipient user ids
     */
    public function __construct(
        public readonly string $messageType,   // text | image | pdf
        public readonly ?string $body,
        public readonly ?string $mediaPath,     // storage-relative path (already stored)
        public readonly ?string $mediaOriginalName,
        public readonly string $audience,       // the chosen group key
        public readonly ?string $audienceLabel,
        public readonly array $recipientIds,
        public readonly ?int $schoolId,         // null = super-admin / all schools
        public readonly int $senderId,
    ) {}
}
