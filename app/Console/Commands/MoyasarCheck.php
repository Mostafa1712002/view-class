<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Answers "are the payment keys actually working", which is otherwise only
 * discovered by a customer whose card was charged.
 */
class MoyasarCheck extends Command
{
    protected $signature = 'moyasar:check {--mode= : test or live, defaults to the configured mode}';

    protected $description = 'Check that the configured Moyasar keys reach the gateway';

    public function handle(): int
    {
        $mode = $this->option('mode') ?: config('moyasar.mode');
        $keys = config("moyasar.keys.$mode");

        if (! $keys) {
            $this->error("Unknown mode [$mode]. Use test or live.");

            return self::FAILURE;
        }

        $this->line("mode: $mode");
        $this->line('site: '.config('moyasar.site'));
        $this->line('publishable: '.($keys['publishable'] ? 'set' : 'MISSING'));
        $this->line('secret: '.($keys['secret'] ? 'set' : 'MISSING'));
        $this->line('webhook secret: '.(config('moyasar.webhook_secret') ? 'set' : 'not set'));

        if (! $keys['secret']) {
            $this->error('No secret key configured for this mode.');

            return self::FAILURE;
        }

        $response = Http::withBasicAuth($keys['secret'], '')
            ->timeout(20)
            ->get(config('moyasar.api_url').'/payments', ['per' => 1]);

        if ($response->failed()) {
            $this->error('Gateway rejected the key: HTTP '.$response->status());

            return self::FAILURE;
        }

        $this->info('Gateway reachable — HTTP '.$response->status()
            .', payments on this account: '.($response->json('meta.total_count') ?? '?'));

        return self::SUCCESS;
    }
}
