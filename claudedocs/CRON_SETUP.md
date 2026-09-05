# ViewClass — Scheduler Cron Setup (prod activation)

Laravel's scheduled jobs (`routes/console.php`) only run if the OS cron calls
`schedule:run` every minute. Without this ONE cron line, every timed feature is dead:
absence auto-messages, evaluation notifications, school-calendar reminders.

## The one line to add on the prod server

Run `crontab -e` (as the deploy user) and add:

```cron
* * * * * cd /var/www/viewclass.newaves-systems.com && sudo -u www-data php artisan schedule:run >> /dev/null 2>&1
```

- Adjust the path if prod deploys elsewhere.
- `sudo -u www-data` matches the web/file owner so the scheduler writes caches/logs as
  the right user (running artisan as root corrupts `bootstrap/cache` + `storage`).
- One line only — Laravel's own scheduler decides which jobs are due each minute.

## Do NOT wipe other crontab entries

If the box hosts other apps, append — don't overwrite. Safe append:

```bash
( crontab -l 2>/dev/null; \
  echo '* * * * * cd /var/www/viewclass.newaves-systems.com && sudo -u www-data php artisan schedule:run >> /dev/null 2>&1' \
) | crontab -
```

## Verify it's registered

```bash
crontab -l | grep viewclass                       # the line is present
cd /var/www/viewclass.newaves-systems.com
sudo -u www-data php artisan schedule:list         # shows due times for each job
```

Expect (as of 2026-09-05):

| Schedule | Command | Purpose |
|----------|---------|---------|
| `0 * * * *` (hourly) | `attendance:send-daily-absence-messages` | Auto absence messages to guardians |
| `0 7 * * *` | `evaluation:notify-close-date` | Evaluation close-date reminders |
| `30 7 * * *` | `evaluation:notify-visit-reminder` | Class-visit reminders |
| `*/5 * * * *` | `school-calendar:send-reminders` | School-calendar reminders |

## ⚠️ Side effect — turning this on activates ALL scheduled jobs at once

Enabling the cron does not just start absence auto-send; it starts every command in
`routes/console.php`. On a fresh prod DB that's fine. On a DB with historical data,
evaluation/calendar reminders may fire on the first run. If that's unwanted on cutover,
temporarily disable a specific job in `routes/console.php` before adding the cron, or add
the cron during a quiet window.

## Feature-specific note — absence auto-send is opt-in per school

`attendance:send-daily-absence-messages` runs hourly but only sends for a school whose
`sms_auto_message_settings` row (`event_type = absent_daily`) has `is_enabled = 1` and a
`send_time` set. It fires once per school when the school's local time (its `timezone`)
passes `send_time`, and dedupes on `attendances.notified_parent`. No school sends until an
admin enables it on the «إدارة رسائل غياب الطالب» page. Manual send works with or without cron.

---
_Live prod (viewclass.newaves-systems.com) already had this cron added on 2026-09-05._
