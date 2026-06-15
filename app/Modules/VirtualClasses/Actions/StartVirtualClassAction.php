<?php

namespace App\Modules\VirtualClasses\Actions;

use App\Models\ActivityLog;
use App\Models\VirtualClass;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;

/**
 * Teacher/host starts a virtual class: marks it live, stamps started_at (drives
 * the "بدأ المعلم" column), and logs the action. Returns the host start URL.
 */
final class StartVirtualClassAction
{
    public function __construct(private VirtualClassRepositoryInterface $repo) {}

    /**
     * @return array{url: ?string}
     */
    public function execute(VirtualClass $vc): array
    {
        if ($vc->status !== 'live') {
            $this->repo->update($vc->id, [
                'status'     => 'live',
                'started_at' => now(),
            ]);
        }

        ActivityLog::log(
            'start_virtual_class',
            "بدء الفصل الافتراضي: {$vc->title}",
            $vc
        );

        // Host opens start_url for Zoom; for external/teams fall back to the link.
        return [
            'url' => $vc->start_url ?: $vc->participantUrl(),
        ];
    }
}
