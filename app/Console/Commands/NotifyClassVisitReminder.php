<?php

namespace App\Console\Commands;

use App\Models\ClassVisit;
use App\Models\Notification;
use App\Modules\Evaluation\Enums\VisitStatus;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Console\Command;

/**
 * Sprint 8 — class-visit reminder (notification trigger: class_visit_reminder).
 *
 * Finds ClassVisit records whose visit_date is TOMORROW, that are not secret,
 * not cancelled/completed, and have notify_teacher = true. Notifies the
 * assigned teacher once per visit per day (idempotent: checks the Notification
 * table before sending).
 *
 * Scheduled daily at 07:30 (see routes/console.php).
 */
class NotifyClassVisitReminder extends Command
{
    protected $signature = 'evaluation:notify-visit-reminder';

    protected $description = 'Remind teachers about class visits scheduled for tomorrow';

    public function handle(EvaluationNotifier $notifier): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $visits = ClassVisit::query()
            ->whereDate('visit_date', $tomorrow)
            ->where('notify_teacher', true)
            ->whereNotIn('status', [
                VisitStatus::Secret->value,
                VisitStatus::Cancelled->value,
                VisitStatus::Completed->value,
            ])
            ->get();

        if ($visits->isEmpty()) {
            $this->info('No class visits to remind for tomorrow.');

            return self::SUCCESS;
        }

        $notified = 0;

        foreach ($visits as $visit) {
            if ($this->alreadyNotifiedToday($visit->id)) {
                continue;
            }

            $notifier->visitReminder($visit);
            $notified++;
        }

        $this->info("Class-visit reminders sent: {$notified}.");

        return self::SUCCESS;
    }

    /** True when this visit already received a reminder notification today. */
    private function alreadyNotifiedToday(int $visitId): bool
    {
        return Notification::query()
            ->where('type', 'class_visit_reminder')
            ->where('data->class_visit_id', $visitId)
            ->whereDate('created_at', today())
            ->exists();
    }
}
