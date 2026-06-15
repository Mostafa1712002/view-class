<?php

namespace App\Modules\Announcements\Services;

use App\Models\Announcement;
use App\Models\Notification;
use App\Modules\Announcements\Repositories\Contracts\AnnouncementRepository;

/**
 * Fans out an announcement to its targeted users on publish.
 *
 * Internal in-app notifications are fully wired (the `notifications` table
 * already has an `announcement` type). SMS / WhatsApp send-hooks are recorded
 * as intent (the toggle is persisted) but the actual provider send is deferred
 * — the existing WhatsappService is attendance-specific and the SMS module has
 * no generic broadcast API yet. See the module note.
 */
class AnnouncementNotifier
{
    public function __construct(private AnnouncementRepository $announcements) {}

    /**
     * @return array{internal:int, sms:int, whatsapp:int}
     */
    public function dispatch(Announcement $announcement): array
    {
        $counts = ['internal' => 0, 'sms' => 0, 'whatsapp' => 0];

        if (!$announcement->isLive()) {
            return $counts;
        }

        if (!$announcement->notify_internal) {
            return $counts;
        }

        $users = $this->announcements->resolveTargetedUsers($announcement);

        foreach ($users as $user) {
            Notification::create([
                'user_id'     => $user->id,
                'type'        => 'announcement',
                'title'       => $announcement->title,
                'body'        => strip_tags((string) $announcement->body),
                'icon'        => 'la-bullhorn',
                'color'       => $announcement->type === 'important' ? 'warning' : 'info',
                'action_url'  => route('announcements.show', $announcement->id),
                'action_text' => 'عرض الإعلان',
                'data'        => ['announcement_id' => $announcement->id],
            ]);
            $counts['internal']++;
        }

        // SMS / WhatsApp send-hooks deferred (flags persisted on the model).
        return $counts;
    }
}
