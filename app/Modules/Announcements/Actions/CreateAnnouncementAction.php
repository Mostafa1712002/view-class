<?php

namespace App\Modules\Announcements\Actions;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Modules\Announcements\DTOs\AnnouncementDto;
use App\Modules\Announcements\Repositories\Contracts\AnnouncementRepository;
use App\Modules\Announcements\Services\AnnouncementNotifier;
use App\Support\HtmlSanitizer;

final class CreateAnnouncementAction
{
    public function __construct(
        private AnnouncementRepository $announcements,
        private AnnouncementNotifier $notifier,
    ) {}

    public function execute(AnnouncementDto $dto): Announcement
    {
        $data = $dto->toModelArray();
        $data['body'] = HtmlSanitizer::clean($data['body'] ?? null);
        if ($dto->status === 'published') {
            $data['published_at'] = now();
        }

        $announcement = $this->announcements->create($data, $dto->userTargetIds, $dto->roleTargetIds);

        $verb = $dto->status === 'published' ? 'نشر' : 'إنشاء مسودة';
        ActivityLog::logCreate($announcement, "{$verb} إعلان: {$announcement->title}");

        if ($dto->status === 'published') {
            $this->notifier->dispatch($announcement);
        }

        return $announcement;
    }
}
