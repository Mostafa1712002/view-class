<?php

namespace App\Console\Commands;

use App\Models\SchoolEvent;
use App\Modules\SchoolCalendar\Services\SchoolEventNotifier;
use Illuminate\Console\Command;

/**
 * Card #233 — pre-event reminders.
 *
 * Finds events with remind_before = true whose start moment falls inside the
 * configured lead-time window (remind_minutes) and that have not yet been
 * reminded, then fans an internal notification out to the targeted users.
 * Idempotent via the school_events.reminded_at stamp.
 *
 * Schedule it every five minutes (see routes/console.php). With no cron the
 * reminder simply won't fire — the toggle is still persisted and an admin can
 * run `php artisan school-calendar:send-reminders` manually.
 */
class SendCalendarReminders extends Command
{
    protected $signature = 'school-calendar:send-reminders';

    protected $description = 'Send reminders for upcoming calendar events';

    public function handle(SchoolEventNotifier $notifier): int
    {
        $now  = now();
        $sent = 0;

        $events = SchoolEvent::query()
            ->with('targets')
            ->where('remind_before', true)
            ->whereNull('reminded_at')
            ->whereNotNull('remind_minutes')
            ->where('start_date', '>=', $now->copy()->subDay()->toDateString())
            ->get();

        foreach ($events as $event) {
            $startsAt = $event->start_date->copy();
            if (! $event->all_day && $event->start_time) {
                [$h, $m] = array_pad(explode(':', (string) $event->start_time), 2, 0);
                $startsAt->setTime((int) $h, (int) $m);
            }

            $remindAt = $startsAt->copy()->subMinutes((int) $event->remind_minutes);

            // Fire once the reminder moment has arrived but the event hasn't passed.
            if ($now->greaterThanOrEqualTo($remindAt) && $now->lessThanOrEqualTo($startsAt)) {
                $notifier->notifyReminder($event);
                $event->forceFill(['reminded_at' => $now])->save();
                $sent++;
            }
        }

        $this->info("Calendar reminders sent for {$sent} event(s).");

        return self::SUCCESS;
    }
}
