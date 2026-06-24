<?php

namespace App\Modules\SchoolCalendar\Services;

use App\Models\Notification;
use App\Models\SchoolEvent;
use App\Modules\SchoolCalendar\Repositories\Contracts\SchoolEventRepository;

/**
 * Fans out an internal in-app notification to the users a calendar event
 * targets. Used both on create (when "إرسال إشعار" is ticked) and by the
 * pre-event reminder command (when "تذكير قبل الحدث" is ticked).
 *
 * SMS / WhatsApp delivery is intentionally NOT wired here — the platform's
 * providers are gated on credentials (see project note); only the internal
 * channel is reliable today.
 */
class SchoolEventNotifier
{
    public function __construct(private SchoolEventRepository $repo) {}

    /** Notify targeted users that an event was scheduled. Returns count sent. */
    public function notifyCreated(SchoolEvent $event): int
    {
        return $this->fan($event, __('school_calendar.notif_new_title'), false);
    }

    /** Notify targeted users that an event is approaching. Returns count sent. */
    public function notifyReminder(SchoolEvent $event): int
    {
        return $this->fan($event, __('school_calendar.notif_reminder_title'), true);
    }

    private function fan(SchoolEvent $event, string $titlePrefix, bool $reminder): int
    {
        $sent = 0;
        $when = $event->start_date->format('Y-m-d')
            . ($event->all_day || ! $event->start_time ? '' : ' ' . substr((string) $event->start_time, 0, 5));

        foreach ($this->repo->resolveTargetedUsers($event) as $user) {
            Notification::create([
                'user_id'     => $user->id,
                'type'        => 'school_event',
                'title'       => $titlePrefix . ': ' . $event->title,
                'body'        => trim(($event->description ? strip_tags($event->description) . ' — ' : '') . $when),
                'icon'        => 'la-calendar',
                'color'       => $reminder ? 'warning' : 'info',
                'action_url'  => route('my.calendar.index'),
                'action_text' => __('school_calendar.notif_action'),
                'data'        => ['school_event_id' => $event->id],
            ]);
            $sent++;
        }

        return $sent;
    }
}
