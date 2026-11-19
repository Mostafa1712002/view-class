<?php

namespace App\Modules\SpecialPrograms\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Modules\SpecialPrograms\Actions\Attempts\FinalizeOverdue;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpExam;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ExamRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\PerformanceLevels;
use Illuminate\Contracts\View\View;

/** «البرامج التعليمية»: exam list, subject cards, final result. */
class ProgramController extends Controller
{
    public function __construct(
        private ExamRepository $exams,
        private EnrollmentRepository $enrollments,
        private SettingsRepository $settings,
    ) {}

    public function index(): View
    {
        $student = auth()->user();
        $exams = $this->exams->forStudent($student);
        $attempts = SpAttempt::where('student_id', $student->id)->whereIn('exam_id', $exams->pluck('id'))->get()->groupBy('exam_id');

        $cards = $exams->map(function (SpExam $e) use ($attempts) {
            $mine = $attempts->get($e->id, collect());
            $done = $mine->where('status', '!=', 'in_progress')->count();
            $total = $e->subjects->count();
            $state = match (true) {
                $total > 0 && $done >= $total => 'completed',
                $mine->contains('status', 'in_progress') => 'in_progress',
                $e->expires_at && $e->expires_at->isPast() && $mine->isEmpty() => 'expired',
                default => 'available',
            };

            return ['exam' => $e, 'done' => $done, 'total' => $total, 'state' => $state];
        });

        return view('special-programs.student.index', ['cards' => $cards, 'enrolled' => $cards->isNotEmpty() || $this->enrollments->programsOf($student->id) !== []]);
    }

    public function exam(int $id): View
    {
        [$exam, $mine] = $this->load($id);
        $prevDone = true;
        $rows = $exam->subjects->map(function ($es) use ($mine, &$prevDone, $exam) {
            $a = $mine->get($es->id);
            $locked = ! $a && ! $prevDone;
            $expired = ! $a && $exam->expires_at && $exam->expires_at->isPast();
            $prevDone = $a && $a->status !== 'in_progress';

            return ['subject' => $es, 'attempt' => $a, 'locked' => $locked, 'expired' => $expired];
        });

        return view('special-programs.student.exam', ['exam' => $exam, 'rows' => $rows]);
    }

    public function final(int $id)
    {
        [$exam, $mine] = $this->load($id);
        $closed = $exam->subjects->every(fn ($es) => $mine->get($es->id) && $mine->get($es->id)->status !== 'in_progress');
        if (! $closed) {
            return redirect()->route('student.programs.exam', $id)->with('error', __('special_programs/student.final_not_ready'));
        }
        $correct = $mine->sum('correct_count');
        $items = $mine->sum('items_count');
        $pct = $items > 0 ? round($correct * 100 / $items, 2) : 0.0;
        $bands = $this->settings->bandsFor($exam->school_id, $exam->program);

        return view('special-programs.student.final-result', [
            'exam' => $exam, 'rows' => $exam->subjects->map(fn ($es) => ['subject' => $es, 'attempt' => $mine->get($es->id)]),
            'pct' => $pct, 'correct' => $correct, 'items' => $items, 'level' => PerformanceLevels::for($pct, $bands),
        ]);
    }

    /** @return array{0: SpExam, 1: \Illuminate\Support\Collection} the exam only if it is in the student's audience (404 otherwise). */
    private function load(int $id): array
    {
        $student = auth()->user();
        $exam = $this->exams->forStudent($student)->firstWhere('id', $id);
        abort_unless($exam, 404);
        // Close overdue in-progress attempts of this exam so statuses shown are true.
        SpAttempt::where('student_id', $student->id)->where('exam_id', $id)->where('status', 'in_progress')
            ->where('deadline_at', '<', now()->subSeconds(FinalizeOverdue::GRACE_SECONDS))
            ->pluck('id')->each(fn ($aid) => app(\App\Modules\SpecialPrograms\Actions\Attempts\FinishAttempt::class)->execute($aid, 'timed_out'));

        return [$exam, SpAttempt::where('student_id', $student->id)->where('exam_id', $id)->get()->keyBy('exam_subject_id')];
    }
}
