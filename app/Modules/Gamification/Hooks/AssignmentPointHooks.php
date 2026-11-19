<?php

namespace App\Modules\Gamification\Hooks;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\User;
use App\Modules\Gamification\Actions\RevokeSourcePoints;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\Gamification\Support\RuleSettings;

/** Points for assignments: student score ratio + teacher interaction/correct ratio. Source = the assignment. */
final class AssignmentPointHooks
{
    private const SOURCE = 'assignment';

    public function __construct(private SyncActivityPoints $sync, private RuleSettings $settings, private RevokeSourcePoints $revoke) {}

    /** $studentId = the graded student (admin grade), null when a student submitted. */
    public function assignmentChanged(Assignment $assignment, ?int $studentId): void
    {
        if ($studentId !== null) {
            $submission = AssignmentSubmission::where('assignment_id', $assignment->id)->where('student_id', $studentId)->first();
            $this->send('student.assignments', $studentId, $assignment, $submission ? $this->ratio($submission) : 0.0);
        }

        if ($assignment->teacher_id) {
            $basis = $this->settings->effective('teacher.assignments', (int) $assignment->school_id)['options']['basis'] ?? 'interaction';
            $this->send('teacher.assignments', (int) $assignment->teacher_id, $assignment, $this->teacherUnits($assignment, $basis));
        }
    }

    public function assignmentDeleted(int $assignmentId): void
    {
        $this->revoke->execute(self::SOURCE, $assignmentId, 'حذف الواجب');
    }

    private function send(string $rule, int $userId, Assignment $assignment, float $units): void
    {
        $this->sync->execute(new ActivityPoints($rule, $userId, self::SOURCE, $assignment->id, $units, auth()->id()));
    }

    private function ratio(AssignmentSubmission $s): float
    {
        $max = (float) $s->assignment->max_score;

        return ($s->status === 'graded' && $s->score !== null && $max > 0) ? max(0.0, min(1.0, (float) $s->score / $max)) : 0.0;
    }

    private function teacherUnits(Assignment $assignment, string $basis): float
    {
        $targeted = User::where('school_id', $assignment->school_id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->where(fn ($q) => $q->where('class_room_id', $assignment->class_id)
                ->orWhereHas('enrolledClasses', fn ($c) => $c->where('classes.id', $assignment->class_id)))
            ->pluck('id');
        if ($targeted->isEmpty()) {
            return 0.0;
        }

        $subs = AssignmentSubmission::with('assignment')->where('assignment_id', $assignment->id)
            ->whereIn('student_id', $targeted)->whereIn('status', ['submitted', 'graded', 'returned'])->get();

        if ($basis === 'correct') {
            $graded = $subs->where('status', 'graded');

            return $graded->isEmpty() ? 0.0 : (float) $graded->avg(fn ($s) => $this->ratio($s));
        }

        return min(1.0, $subs->count() / $targeted->count());
    }
}
