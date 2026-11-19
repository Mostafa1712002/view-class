<?php

namespace App\Modules\SpecialPrograms\Actions\Attempts;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpExam;

class LogExit
{
    public function __construct(private FinishAttempt $finish) {}

    public function execute(int $attemptId, User $student): SpAttempt
    {
        $attempt = SpAttempt::where('student_id', $student->id)->findOrFail($attemptId);
        if ($attempt->status !== 'in_progress') {
            return $attempt;
        }
        if (now()->greaterThan($attempt->deadline_at->copy()->addSeconds(FinalizeOverdue::GRACE_SECONDS))) {
            return $this->finish->execute($attempt->id, 'timed_out');
        }
        SpAttempt::where('id', $attemptId)->where('status', 'in_progress')->increment('exit_count');
        $attempt->refresh();
        $max = SpExam::withTrashed()->whereKey($attempt->exam_id)->value('max_exit_attempts');
        if ($max !== null && $attempt->exit_count >= $max) {
            return $this->finish->execute($attempt->id, 'auto_ended');
        }

        return $attempt;
    }
}
