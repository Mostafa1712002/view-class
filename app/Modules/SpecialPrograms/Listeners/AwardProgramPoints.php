<?php

namespace App\Modules\SpecialPrograms\Listeners;

use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\SpecialPrograms\Events\ProgramAttemptCompleted;
use Carbon\CarbonImmutable;

/**
 * Bridge to the points module (#371 contract): one key per attempt, units = correct answers.
 * The attempt is the source because sp_attempts is unique per (exam subject, student), so a
 * multi-subject exam earns once per subject and a re-fired event writes nothing new.
 */
final class AwardProgramPoints
{
    public const RULES = [
        'qiyas' => 'student.correct_answers.ana_wal_qudurat',
        'tahsili' => 'student.correct_answers.tahsili',
        'nafis' => 'student.correct_answers.nafes',
    ];

    public function __construct(private SyncActivityPoints $sync) {}

    public function handle(ProgramAttemptCompleted $e): void
    {
        $rule = self::RULES[$e->program] ?? null;
        if (! $rule) {
            return;
        }

        // The attempt is already closed: a points failure must never turn the finish request into an error.
        rescue(fn () => $this->sync->execute(new ActivityPoints(
            ruleKey: $rule,
            userId: $e->studentId,
            sourceType: 'program_test',
            sourceId: $e->attemptId,
            units: $e->correctCount,
            occurredAt: CarbonImmutable::instance($e->completedAt),
        )), null, true);
    }
}
