<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sprint 8 — close-date-approaching reminders for evaluation forms.
Schedule::command('evaluation:notify-close-date')->dailyAt('07:00');

// Sprint 8 — class-visit reminder (tomorrow's visits).
Schedule::command('evaluation:notify-visit-reminder')->dailyAt('07:30');
