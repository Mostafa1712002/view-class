<?php

namespace App\Modules\SpecialPrograms\Actions\Attempts;

use App\Modules\SpecialPrograms\Models\SpAttempt;

class FinalizeOverdue
{
    public const GRACE_SECONDS = 5;

    public function __construct(private FinishAttempt $finish) {}

    /** @return int attempts closed */
    public function execute(): int
    {
        $n = 0;
        SpAttempt::where('status', 'in_progress')->where('deadline_at', '<', now()->subSeconds(self::GRACE_SECONDS))
            ->pluck('id')->each(function ($id) use (&$n) {
                $n += $this->finish->execute($id, 'timed_out')->status === 'timed_out' ? 1 : 0;
            });

        return $n;
    }
}
