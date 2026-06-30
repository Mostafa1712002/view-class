<?php

namespace App\Modules\SmsServices\Services;

use App\Modules\SmsServices\Drivers\HttpProviderDriver;
use App\Modules\SmsServices\Drivers\PendingDriver;
use App\Modules\SmsServices\Drivers\SandboxDriver;
use App\Modules\SmsServices\Drivers\SmsDriverInterface;
use App\Modules\SmsServices\Models\SchoolSmsSetting;

/**
 * Resolves the SMS driver for a school's connection settings.
 *
 * Selection rule (the core "real vs stubbed" boundary):
 *  - settings have BOTH api_key and api_secret → HttpProviderDriver (real send)
 *  - else, service active + sandbox on        → SandboxDriver (sent→delivered, no network)
 *  - otherwise                                → PendingDriver (parks the message as 'queued')
 *
 * Only a real gateway acceptance yields 'sent'; the sandbox is explicit and
 * opt-in (school must be activated), and self-identifies via provider 'sandbox'.
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

    /**
     * Sandbox is used when there is no real gateway, the school's service is
     * active, and the global sandbox switch is on. This makes the SMS index
     * power-toggle (is_active) the real activation control.
     */
    public function usingSandbox(): bool
    {
        return ! $this->hasGateway()
            && (bool) ($this->setting?->is_active)
            && (bool) config('messaging.sms.sandbox', true);
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

        if ($this->usingSandbox()) {
            return new SandboxDriver();
        }

        return new PendingDriver();
    }

    public function provider(): string
    {
        if ($this->usingSandbox()) {
            return 'sandbox';
        }

        return $this->setting->provider ?? 'pending';
    }
}
