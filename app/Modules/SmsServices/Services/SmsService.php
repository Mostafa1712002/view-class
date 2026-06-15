<?php

namespace App\Modules\SmsServices\Services;

use App\Modules\SmsServices\Drivers\HttpProviderDriver;
use App\Modules\SmsServices\Drivers\PendingDriver;
use App\Modules\SmsServices\Drivers\SmsDriverInterface;
use App\Modules\SmsServices\Models\SchoolSmsSetting;

/**
 * Resolves the SMS driver for a school's connection settings.
 *
 * Selection rule (the core "real vs stubbed" boundary):
 *  - settings have BOTH api_key and api_secret → HttpProviderDriver (real send)
 *  - otherwise → PendingDriver (persists + parks the message as 'queued')
 *
 * No code path fabricates a 'sent' status without a real gateway acceptance.
 */
final class SmsService
{
    public function __construct(private readonly ?SchoolSmsSetting $setting) {}

    public function hasGateway(): bool
    {
        return $this->setting
            && ! empty($this->setting->api_key)
            && ! empty($this->setting->api_secret);
    }

    public function resolveDriver(): SmsDriverInterface
    {
        if ($this->hasGateway()) {
            return new HttpProviderDriver(
                apiKey: (string) $this->setting->api_key,
                apiSecret: (string) $this->setting->api_secret,
                sender: $this->setting->defaultSender?->name_en,
                endpoint: $this->setting->api_url ?? null,
            );
        }

        return new PendingDriver();
    }

    public function provider(): string
    {
        return $this->setting->provider ?? 'pending';
    }
}
