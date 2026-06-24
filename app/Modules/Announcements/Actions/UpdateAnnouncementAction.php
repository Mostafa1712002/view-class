<?php

namespace App\Modules\Announcements\Actions;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Modules\Announcements\DTOs\AnnouncementDto;
use App\Modules\Announcements\Repositories\Contracts\AnnouncementRepository;
use App\Modules\Announcements\Services\AnnouncementNotifier;
use App\Support\HtmlSanitizer;

final class UpdateAnnouncementAction
{
    public function __construct(
        private AnnouncementRepository $announcements,
        private AnnouncementNotifier $notifier,
    ) {}

    public function execute(Announcement $announcement, AnnouncementDto $dto): Announcement
    {
        $old = $announcement->toArray();
        $wasPublished = $announcement->status === 'published';

        $data = $dto->toModelArray();
        $data['body'] = HtmlSanitizer::clean($data['body'] ?? null);
        // Stamp published_at on first transition to published.
        if ($dto->status === 'published' && $announcement->published_at === null) {
            $data['published_at'] = now();
        }

        $announcement = $this->announcements->update($announcement, $data, $dto->userTargetIds, $dto->roleTargetIds, $dto->jobTitleIds);

        ActivityLog::logUpdate($announcement, "تعديل إعلان: {$announcement->title}", $old);

        // Fan out only on the draft -> published transition.
        if (!$wasPublished && $dto->status === 'published') {
            $this->notifier->dispatch($announcement);
        }

        return $announcement;
    }
}
