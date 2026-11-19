<?php

namespace App\Modules\SpecialPrograms\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Modules\SpecialPrograms\Actions\Attempts\AttemptException;
use App\Modules\SpecialPrograms\Actions\Attempts\FinishAttempt;
use App\Modules\SpecialPrograms\Actions\Attempts\LogExit;
use App\Modules\SpecialPrograms\Actions\Attempts\SaveAnswer;
use App\Modules\SpecialPrograms\Actions\Attempts\StartSubject;
use App\Modules\SpecialPrograms\Models\SpAttempt;
use App\Modules\SpecialPrograms\Models\SpExamSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\AttemptRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\PerformanceLevels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttemptController extends Controller
{
    public function __construct(private AttemptRepository $attempts, private SettingsRepository $settings) {}

    public function start(int $examId, int $subjectId): RedirectResponse
    {
        $es = SpExamSubject::where('exam_id', $examId)->findOrFail($subjectId);
        try {
            $attempt = app(StartSubject::class)->execute($es->id, auth()->user());
        } catch (AttemptException $e) {
            if ($e->reason === 'already_finished') {
                $done = SpAttempt::where('exam_subject_id', $es->id)->where('student_id', auth()->id())->first();
                if ($done) {
                    return redirect()->route('student.programs.result', $done->id);
                }
            }

            return redirect()->route('student.programs.exam', $examId)->with('error', $this->message($e, $es));
        }

        return redirect()->route('student.programs.take', $attempt->id);
    }

    public function take(int $id)
    {
        $attempt = $this->attempts->findForStudent($id, auth()->user());
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.programs.result', $attempt->id);
        }
        $attempt->update(['last_seen_at' => now()]);
        $exam = $attempt->exam()->withTrashed()->first();
        $items = $attempt->items()->get();

        // One screen per independent question, or per passage (its sub-questions stay together).
        $screens = [];
        foreach ($items as $item) {
            $last = array_key_last($screens);
            if ($item->passage_id && $last !== null && $screens[$last][0]->passage_id === $item->passage_id) {
                $screens[$last][] = $item;
            } else {
                $screens[] = [$item];
            }
        }

        return view('special-programs.student.take', [
            'attempt' => $attempt, 'exam' => $exam, 'subject' => $attempt->examSubject->subject,
            'screens' => $screens, 'passages' => $this->passages($items),
            'remaining' => max(0, (int) now()->diffInSeconds($attempt->deadline_at, false)),
            'antiCheat' => $exam->max_exit_attempts !== null,
        ]);
    }

    public function answer(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['item_id' => 'required|integer', 'option' => 'required|integer|min:0']);
        try {
            $res = app(SaveAnswer::class)->execute($id, auth()->user(), (int) $data['item_id'], (int) $data['option']);
        } catch (AttemptException $e) {
            return $this->refusal($e, $id);
        }

        return response()->json($res);
    }

    public function heartbeat(int $id): JsonResponse
    {
        $a = $this->attempts->findForStudent($id, auth()->user());
        if ($a->status === 'in_progress') {
            $a->update(['last_seen_at' => now()]);
        }

        return response()->json([
            'status' => $a->status, 'remaining_seconds' => $a->status === 'in_progress' ? max(0, (int) now()->diffInSeconds($a->deadline_at, false)) : 0,
            'result_url' => route('student.programs.result', $a->id),
        ]);
    }

    public function exit(int $id): JsonResponse
    {
        $a = app(LogExit::class)->execute($id, auth()->user());

        return response()->json(['status' => $a->status, 'exit_count' => $a->exit_count, 'result_url' => route('student.programs.result', $a->id)]);
    }

    public function finish(Request $request, int $id)
    {
        $a = $this->attempts->findForStudent($id, auth()->user());
        $a = app(FinishAttempt::class)->execute($a->id, 'completed');
        $url = route('student.programs.result', $a->id);

        return $request->expectsJson() ? response()->json(['status' => $a->status, 'result_url' => $url]) : redirect($url);
    }

    public function result(int $id)
    {
        $attempt = $this->attempts->findForStudent($id, auth()->user());
        if ($attempt->status === 'in_progress') {
            return redirect()->route('student.programs.take', $attempt->id);
        }
        $attempt->load(['exam' => fn ($q) => $q->withTrashed(), 'examSubject.subject']);
        $items = $attempt->items()->get();
        $bands = $this->settings->bandsFor($attempt->school_id, $attempt->program);
        $groupBy = $attempt->program === 'tahsili' ? 'difficulty' : 'sp_skill_id';
        $skillNames = DB::table('sp_skills')->whereIn('id', $items->pluck('sp_skill_id')->filter())->pluck('name', 'id');
        $breakdown = $items->groupBy(fn ($i) => $i->{$groupBy} ?? '')->map(fn ($g, $k) => [
            'label' => $groupBy === 'difficulty' ? __('special_programs.difficulty.'.$k) : ($skillNames[$k] ?? __('special_programs/student.no_skill')),
            'total' => $g->count(), 'correct' => $g->where('is_correct', true)->count(),
        ])->values();
        $next = SpExamSubject::where('exam_id', $attempt->exam_id)->where('order_index', '>', $attempt->examSubject->order_index)->exists();

        return view('special-programs.student.subject-result', [
            'attempt' => $attempt, 'items' => $items, 'passages' => $this->passages($items), 'breakdown' => $breakdown,
            'level' => PerformanceLevels::for((float) $attempt->score_pct, $bands), 'hasNext' => $next,
            'multi' => SpExamSubject::where('exam_id', $attempt->exam_id)->count() > 1,
        ]);
    }

    /** @return array<int, object> passage id => {passage_text, passage_image} */
    private function passages($items): array
    {
        $ids = $items->pluck('passage_id')->filter()->unique()->all();

        return $ids ? DB::table('passages')->whereIn('id', $ids)->get(['id', 'passage_text', 'passage_image'])->keyBy('id')->all() : [];
    }

    private function refusal(AttemptException $e, int $attemptId): JsonResponse
    {
        return response()->json([
            'saved' => false, 'reason' => $e->reason, 'message' => $this->message($e),
            'result_url' => route('student.programs.result', $attemptId),
        ], 422);
    }

    private function message(AttemptException $e, ?SpExamSubject $es = null): string
    {
        $subject = $es ? $this->previousSubjectName($es) : '';

        return __('special_programs/student.reasons.'.$e->reason, ['subject' => $subject]);
    }

    private function previousSubjectName(SpExamSubject $es): string
    {
        $prev = SpExamSubject::with('subject')->where('exam_id', $es->exam_id)->where('order_index', '<', $es->order_index)
            ->orderByDesc('order_index')->first();

        return (string) $prev?->subject?->name_ar;
    }
}
