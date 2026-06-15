<?php

namespace App\Modules\VirtualClasses\Actions;

use App\Models\ActivityLog;
use App\Models\VirtualClass;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;

/**
 * Record a participant entering a virtual class. Captures the entry timestamp in
 * the per-student log (the recalc source) and logs the action. Returns the URL
 * the participant should open (platform-aware).
 */
final class JoinVirtualClassAction
{
    public function __construct(private VirtualClassRepositoryInterface $repo) {}

    /**
     * @return array{url: ?string, attendee_id: int}
     */
    public function execute(VirtualClass $vc, int $studentId): array
    {
        $attendee = $this->repo->recordEntry($vc->id, $studentId, $vc->school_id);

        ActivityLog::log(
            'join_virtual_class',
            "دخول فصل افتراضي: {$vc->title}",
            $vc
        );

        return [
            'url'         => $vc->participantUrl(),
            'attendee_id' => $attendee->id,
        ];
    }
}
