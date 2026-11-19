<?php

namespace Tests\Feature\RawaniHub;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\TestCase;

class HubWiringTest extends TestCase
{
    // Bare base class: Tests\TestCase seeds roles, and this test needs no database.
    public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function test_disabled_by_default_no_routes_no_schedule(): void
    {
        $this->assertFalse(config('rawani_hub.enabled'));
        foreach (['admin.hub.packages.index', 'admin.hub.payments.index', 'admin.hub.directory.index', 'admin.hub.platforms.overview', 'hub.register',
            'my.packages.index', 'my.packages.accounts.open', 'hub.moyasar.webhook'] as $name) {
            $this->assertFalse(Route::has($name), $name);
        }
        $this->get('/admin/hub/packages')->assertNotFound();
        $this->get('/register')->assertNotFound();
        $this->get('/my/packages')->assertNotFound();
        $this->post('/api/hub/moyasar/webhook', ['secret_token' => 'x'])->assertNotFound();
        $this->get('/login')->assertOk()->assertDontSee('/register');
        $this->assertArrayNotHasKey('hub:reconcile-payments', \Illuminate\Support\Facades\Artisan::all());
        $this->assertArrayNotHasKey('hub:mirror', \Illuminate\Support\Facades\Artisan::all());

        $commands = collect(app(Schedule::class)->events())->pluck('command')->implode(' ');
        $this->assertStringNotContainsString('hub:', $commands);
    }
}
