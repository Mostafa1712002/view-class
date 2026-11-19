<?php

namespace App\Modules\RawaniHub\DTOs;

/** Absolute state Rawani sends for one platform account (design §3.2 / §4.3). */
final class AccountState
{
    /**
     * @param  array<string,mixed>  $profile
     * @param  list<array{product:string,from:string,until:string}>  $entitlements
     * @param  list<string>  $missing  required fields the person lacks (empty = ready to send)
     */
    public function __construct(
        public readonly array $profile,
        public readonly bool $enabled,
        public readonly array $entitlements,
        public readonly ?string $password = null,
        public readonly ?string $linkExternalId = null,
        public readonly array $missing = [],
    ) {}

    public function ready(): bool
    {
        return $this->missing === [];
    }

    /** Body of PUT /accounts/{role}/{rawani_id}; optional keys are omitted, not null. */
    public function toPayload(): array
    {
        $payload = ['profile' => $this->profile, 'enabled' => $this->enabled];
        if ($this->password !== null) {
            $payload['password'] = $this->password;
        }
        if ($this->linkExternalId !== null) {
            $payload['link_external_id'] = $this->linkExternalId;
        }
        $payload['entitlements'] = $this->entitlements;

        return $payload;
    }
}
