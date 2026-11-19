<?php

namespace App\Modules\SpecialPrograms\Actions\Attempts;

use App\Models\User;
use App\Modules\SpecialPrograms\Actions\Picking\PickerFor;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpExam;
use App\Modules\SpecialPrograms\Models\SpExamSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ExamRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\QuestionPoolRepository;
use Illuminate\Support\Facades\DB;

class StartSubject
{
    public function __construct(
        private EnrollmentRepository $enrolments,
        private ExamRepository $exams,
        private QuestionPoolRepository $pool,
        private FinishAttempt $finish,
    ) {}

    /** Returns the in-progress attempt (new or resumed). Throws AttemptException on any refusal. */
    public function execute(int $examSubjectId, User $student): SpAttempt
    {
        $es = SpExamSubject::findOrFail($examSubjectId);
        $exam = SpExam::findOrFail($es->exam_id); // soft-deleted exams are 404
        if (! $this->enrolments->isEnrolled($student->id, $exam->program) || ! $this->exams->isInAudience($exam, $student)) {
            throw new AttemptException('not_eligible');
        }

        $existing = SpAttempt::where('exam_subject_id', $es->id)->where('student_id', $student->id)->first();
        if ($existing) {
            if ($existing->status === 'in_progress'
                && now()->greaterThan($existing->deadline_at->copy()->addSeconds(FinalizeOverdue::GRACE_SECONDS))) {
                $this->finish->execute($existing->id, 'timed_out');
                $existing->refresh();
            }
            if ($existing->status !== 'in_progress') {
                throw new AttemptException('already_finished');
            }

            return $existing;
        }

        if ($exam->expires_at && now()->greaterThan($exam->expires_at)) {
            throw new AttemptException('expired');
        }
        $this->assertEarlierSubjectsDone($exam, $es, $student);

        return DB::transaction(function () use ($es, $exam, $student) {
            $now = now();
            // Unique (exam_subject_id, student_id): a racing second start inserts nothing and then waits on the row lock below.
            SpAttempt::insertOrIgnore([
                'school_id' => $exam->school_id, 'program' => $exam->program, 'exam_id' => $exam->id, 'exam_subject_id' => $es->id,
                'student_id' => $student->id, 'status' => 'in_progress', 'started_at' => $now,
                'deadline_at' => $now->copy()->addMinutes((int) $es->duration_minutes), 'created_at' => $now, 'updated_at' => $now,
            ]);
            $attempt = SpAttempt::query()->lockForUpdate()
                ->where('exam_subject_id', $es->id)->where('student_id', $student->id)->firstOrFail();
            if (! $attempt->items()->exists()) {
                $this->generateItems($attempt, $es, $exam->program, $student);
            }

            return $attempt->refresh();
        });
    }

    private function assertEarlierSubjectsDone(SpExam $exam, SpExamSubject $es, User $student): void
    {
        $earlier = SpExamSubject::where('exam_id', $exam->id)->where('order_index', '<', $es->order_index)->pluck('id');
        if ($earlier->isEmpty()) {
            return;
        }
        $done = SpAttempt::where('student_id', $student->id)->whereIn('exam_subject_id', $earlier)
            ->where('status', '!=', 'in_progress')->count();
        if ($done < $earlier->count()) {
            throw new AttemptException('locked');
        }
    }

    private function generateItems(SpAttempt $attempt, SpExamSubject $es, string $program, User $student): void
    {
        $ids = PickerFor::program($program)->pick($es, $student);
        if ($ids === []) {
            throw new AttemptException('no_questions'); // rolls back: the student keeps a fresh start later
        }
        $snap = $this->pool->snapshot($ids);
        $rows = [];
        foreach (array_values(array_filter($ids, fn ($id) => isset($snap[$id]))) as $i => $id) {
            $q = $snap[$id];
            $rows[] = [
                'attempt_id' => $attempt->id, 'bank_question_id' => $q['bank_question_id'], 'sp_skill_id' => $q['sp_skill_id'],
                'difficulty' => $q['difficulty'], 'passage_id' => $q['passage_id'], 'sub_index' => $q['sub_index'] ?? 0,
                'order_index' => $i + 1, 'stem' => $q['stem'], 'image_path' => $q['image_path'],
                'options' => json_encode($q['options'], JSON_UNESCAPED_UNICODE), 'correct_index' => $q['correct_index'],
            ];
        }
        if ($rows === []) {
            throw new AttemptException('no_questions');
        }
        DB::table('sp_attempt_items')->insert($rows);
        $attempt->update(['items_count' => count($rows)]);
    }
}
