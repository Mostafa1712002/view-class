<?php

namespace App\Modules\SpecialPrograms\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired once per attempt, after commit (contract: design.md §10). Payload is scalars only. */
final class ProgramAttemptCompleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $attemptId,
        public readonly int $studentId,
        public readonly int $schoolId,
        public readonly string $program,
        public readonly int $examId,
        public readonly int $examSubjectId,
        public readonly string $subjectCode,
        public readonly int $correctCount,
        public readonly int $totalCount,
        public readonly float $scorePct,
        public readonly string $status,
        public readonly bool $examCompleted,
        public readonly \DateTimeImmutable $completedAt,
    ) {}
}
