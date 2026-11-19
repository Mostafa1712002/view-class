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
        $this->assertFalse(Route::has('admin.hub.packages.index'));
        $this->get('/admin/hub/packages')->assertNotFound();

        $commands = collect(app(Schedule::class)->events())->pluck('command')->implode(' ');
        $this->assertStringNotContainsString('hub:', $commands);
    }
}
