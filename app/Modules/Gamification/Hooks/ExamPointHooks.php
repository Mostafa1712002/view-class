<?php

namespace App\Modules\Gamification\Hooks;

use App\Models\Exam;
use App\Models\StudentExam;
use App\Modules\Gamification\Actions\RevokeSourcePoints;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\Gamification\Support\ClassStudents;
use App\Modules\Gamification\Support\RuleSettings;
use Illuminate\Support\Facades\DB;

/** Points hooks for exams: one call per host event, everything else lives here. */
final class ExamPointHooks
{
    public function __construct(private SyncActivityPoints $sync, private RevokeSourcePoints $revoke, private RuleSettings $settings) {}

    /** After an attempt was submitted / auto-ended / reopened: re-sync the student's key and the teacher's key. */
    public function examAttemptChanged(Exam $exam, int $studentId): void
    {
        HostSafe::run(function () use ($exam, $studentId) {
            $best = $this->bestCorrect($exam->id);
            $actor = auth()->id();

            $this->sync->execute(new ActivityPoints(
                'student.correct_answers.subjects', $studentId, 'exam', $exam->id,
                (float) ($best[$studentId] ?? 0), $actor,
            ));

            if ($exam->teacher_id) {
                $this->sync->execute(new ActivityPoints(
                    'teacher.exams', (int) $exam->teacher_id, 'exam', $exam->id,
                    $this->teacherUnits($exam, $best), $actor,
                ));
            }
        });
    }

    /** Call AFTER the delete succeeded. */
    public function examDeleted(int $examId): void
    {
        HostSafe::run(function () use ($examId) {
            $this->revoke->execute('exam', $examId, 'حذف الاختبار');
        });
    }

    /** @return array<int,int> student_id => best correct count over submitted attempts */
    private function bestCorrect(int $examId): array
    {
        $rows = StudentExam::query()
            ->where('student_exams.exam_id', $examId)
            ->whereNotNull('student_exams.submitted_at')
            ->leftJoin('student_answers as a', fn ($j) => $j->on('a.student_exam_id', '=', 'student_exams.id')->where('a.is_correct', true))
            ->groupBy('student_exams.id', 'student_exams.student_id')
            ->select('student_exams.student_id', DB::raw('count(a.id) as correct'))
            ->get();

        $best = [];
        foreach ($rows as $r) {
            $best[$r->student_id] = max($best[$r->student_id] ?? 0, (int) $r->correct);
        }

        return $best;
    }

    /** Ratio in [0,1]; basis interaction = submitted ÷ targeted, correct = avg(best correct ÷ questions). */
    private function teacherUnits(Exam $exam, array $best): float
    {
        $schoolId = (int) ($exam->teacher?->school_id ?? 0);
        $ids = ClassStudents::ids($exam->class_id ? (int) $exam->class_id : null, $schoolId ?: null);
        $targeted = count($ids);
        // only targeted students count, so the ratio can't pass 1 or mix in outsiders
        $best = array_intersect_key($best, array_flip($ids));
        $submitted = count($best);
        if ($targeted === 0 || $submitted === 0) {
            return 0.0;
        }

        $basis = $this->settings->effective('teacher.exams', $schoolId)['options']['basis'] ?? 'interaction';

        if ($basis === 'correct') {
            $questions = $exam->questions()->count();

            return $questions > 0 ? min(1.0, array_sum($best) / $submitted / $questions) : 0.0;
        }

        return min(1.0, $submitted / $targeted);
    }
}
