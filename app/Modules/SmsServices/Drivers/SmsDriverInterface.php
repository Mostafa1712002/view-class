<?php

namespace App\Modules\SmsServices\Drivers;

interface SmsDriverInterface
{
    /**
     * Hand a message to the gateway.
     *
     * IMPORTANT contract: the status returned MUST honestly reflect what
     * happened. When no real gateway is wired (credentials absent), the driver
     * returns status 'queued' — the message is persisted + parked, NOT marked
     * 'sent'. Only a driver that actually got an acceptance from a provider may
     * return 'sent'.
     *
     * @return array{status:string, provider_response:?string, failure_reason:?string}
     *         status ∈ queued|sent|delivered|failed
     *         ('delivered' is returned by SandboxDriver, which simulates a fully
     *          delivered message synchronously without contacting a real gateway.)
     */
    public function send(string $to, string $message): array;
}
