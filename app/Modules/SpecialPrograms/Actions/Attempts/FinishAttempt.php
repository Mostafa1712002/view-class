<?php

namespace App\Modules\SpecialPrograms\Actions\Attempts;

use App\Modules\SpecialPrograms\Events\ProgramAttemptCompleted;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpAttemptItem;
use Illuminate\Support\Facades\DB;

/** Closes an attempt exactly once; the conditional UPDATE is the guard for the event (design §7, §10). */
class FinishAttempt
{
    /** @param string $reason completed | timed_out | auto_ended ('completed' becomes timed_out when past the deadline) */
    public function execute(int $attemptId, string $reason = 'completed'): SpAttempt
    {
        $event = null;

        DB::transaction(function () use ($attemptId, $reason, &$event) {
            $attempt = SpAttempt::query()->lockForUpdate()->findOrFail($attemptId);
            if ($attempt->status !== 'in_progress') {
                return;
            }
            $now = now();
            $status = $reason === 'completed' && $now->greaterThan($attempt->deadline_at) ? 'timed_out' : $reason;
            $total = SpAttemptItem::where('attempt_id', $attemptId)->count();
            $answered = SpAttemptItem::where('attempt_id', $attemptId)->whereNotNull('answer_index')->count();
            $correct = SpAttemptItem::where('attempt_id', $attemptId)->where('is_correct', true)->count();
            $score = $total > 0 ? round($correct * 100 / $total, 2) : 0.0;
            $spent = max(0, min($now->getTimestamp(), $attempt->deadline_at->getTimestamp()) - $attempt->started_at->getTimestamp());

            $changed = SpAttempt::where('id', $attemptId)->where('status', 'in_progress')->update([
                'status' => $status, 'ended_at' => $now, 'items_count' => $total, 'answered_count' => $answered,
                'correct_count' => $correct, 'score_pct' => $score, 'time_spent_seconds' => $spent,
                'completed_event_at' => $now, 'updated_at' => $now,
            ]);
            if ($changed !== 1) {
                return;
            }

            $exam = $attempt->exam()->withTrashed()->first();
            $subjectIds = DB::table('sp_exam_subjects')->where('exam_id', $attempt->exam_id)->pluck('id');
            $closed = SpAttempt::where('exam_id', $attempt->exam_id)->where('student_id', $attempt->student_id)
                ->whereIn('exam_subject_id', $subjectIds)->where('status', '!=', 'in_progress')->count();
            $code = (string) DB::table('sp_exam_subjects as es')->join('sp_subjects as s', 's.id', '=', 'es.sp_subject_id')
                ->where('es.id', $attempt->exam_subject_id)->value('s.code');

            $event = new ProgramAttemptCompleted(
                $attempt->id, $attempt->student_id, $attempt->school_id, $exam?->program ?? $attempt->program, $attempt->exam_id,
                $attempt->exam_subject_id, $code, $correct, $total, (float) $score, $status,
                $closed >= $subjectIds->count(), $now->toDateTimeImmutable(),
            );
        });

        // ShouldDispatchAfterCommit also defers this if a caller wraps us in an outer transaction.
        if ($event) {
            event($event);
        }

        return SpAttempt::findOrFail($attemptId);
    }
}
