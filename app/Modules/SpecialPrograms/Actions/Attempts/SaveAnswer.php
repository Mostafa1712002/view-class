<?php

namespace App\Modules\SpecialPrograms\Actions\Attempts;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpAttemptItem;
use Illuminate\Support\Facades\DB;

class SaveAnswer
{
    public function __construct(private FinishAttempt $finish) {}

    /** @return array{saved: bool, remaining_seconds: int} never reveals correctness */
    public function execute(int $attemptId, User $student, int $itemId, int $option): array
    {
        $attempt = SpAttempt::where('student_id', $student->id)->findOrFail($attemptId);
        if ($attempt->status === 'in_progress' && now()->greaterThan($attempt->deadline_at->copy()->addSeconds(FinalizeOverdue::GRACE_SECONDS))) {
            $this->finish->execute($attempt->id, 'timed_out');
            throw new AttemptException('late');
        }

        return DB::transaction(function () use ($attemptId, $student, $itemId, $option) {
            // Same row lock as FinishAttempt, so an answer cannot slip in after scoring.
            $attempt = SpAttempt::query()->lockForUpdate()->where('student_id', $student->id)->findOrFail($attemptId);
            if ($attempt->status !== 'in_progress') {
                throw new AttemptException('closed');
            }
            if (now()->greaterThan($attempt->deadline_at->copy()->addSeconds(FinalizeOverdue::GRACE_SECONDS))) {
                throw new AttemptException('late');
            }
            $item = SpAttemptItem::where('attempt_id', $attemptId)->findOrFail($itemId);
            if ($option < 0 || $option >= count($item->options ?? [])) {
                throw new AttemptException('invalid_option');
            }
            $item->update(['answer_index' => $option, 'is_correct' => $item->correct_index !== null && $option === (int) $item->correct_index, 'answered_at' => now()]);
            $attempt->update(['last_seen_at' => now()]);

            return ['saved' => true, 'remaining_seconds' => max(0, (int) now()->diffInSeconds($attempt->deadline_at, false))];
        });
    }
}
