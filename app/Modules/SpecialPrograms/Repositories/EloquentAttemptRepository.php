<?php

namespace App\Modules\SpecialPrograms\Repositories;

use App\Models\User;
use App\Modules\SpecialPrograms\Actions\Attempts\FinalizeOverdue;
use App\Modules\SpecialPrograms\Actions\Attempts\FinishAttempt;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpExam;
use App\Modules\SpecialPrograms\Models\SpExamSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\AttemptRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ExamRepository;
use Illuminate\Support\Collection;

class EloquentAttemptRepository implements AttemptRepository
{
    public function __construct(private FinishAttempt $finish) {}

    public function findForStudent(int $attemptId, User $student): SpAttempt
    {
        $attempt = SpAttempt::where('student_id', $student->id)->findOrFail($attemptId);
        if ($attempt->status === 'in_progress'
            && now()->greaterThan($attempt->deadline_at->copy()->addSeconds(FinalizeOverdue::GRACE_SECONDS))) {
            $attempt = $this->finish->execute($attempt->id, 'timed_out');
        }

        return $attempt;
    }

    public function forExamAndStudent(int $examId, int $studentId): Collection
    {
        return SpAttempt::where('exam_id', $examId)->where('student_id', $studentId)->get();
    }

    public function monitor(SpExam $exam): Collection
    {
        $students = app(ExamRepository::class)->audienceQuery($exam)->get();
        $attempts = SpAttempt::where('exam_id', $exam->id)->get()->keyBy(fn ($a) => $a->exam_subject_id.'-'.$a->student_id);
        $rows = collect();
        foreach (SpExamSubject::with('subject')->where('exam_id', $exam->id)->orderBy('order_index')->get() as $es) {
            foreach ($students as $s) {
                $a = $attempts->get($es->id.'-'.$s->id);
                $rows->push([
                    'exam_subject_id' => $es->id, 'subject_name' => $es->subject?->name_ar, 'student_id' => $s->id,
                    'student_name' => $s->name, 'attempt_id' => $a?->id, 'status' => $a?->status ?? 'not_started',
                    'score_pct' => $a?->score_pct, 'answered_count' => $a?->answered_count, 'items_count' => $a?->items_count,
                    'ended_at' => $a?->ended_at,
                ]);
            }
        }

        return $rows;
    }
}
