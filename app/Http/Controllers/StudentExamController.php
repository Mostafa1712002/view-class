<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamExitAttempt;
use App\Models\StudentAnswer;
use App\Models\StudentExam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Student-facing exam taking flow.
 *
 * === Exams card (ex) ===
 * Lets an enrolled student open an available exam, start an attempt, answer
 * the questions and submit. Auto-gradable questions (MCQ / true-false) are
 * graded immediately; manual types stay ungraded for the teacher.
 */
class StudentExamController extends Controller
{
    /**
     * Show the exam: either the take form (active attempt) or its result
     * (already submitted).
     */
    public function show(Exam $exam): View|RedirectResponse
    {
        $student = auth()->user();

        $guard = $this->guard($exam, $student);
        if ($guard) {
            return $guard;
        }

        // If the student already submitted this exam, send them to the result.
        $submitted = $exam->studentExams()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->first();

        if ($submitted) {
            return redirect()->route('student.exams.result', $exam);
        }

        $exam->load(['subject', 'questions']);

        // Resume an in-progress attempt or present the intro to start one.
        $attempt = $exam->studentExams()
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        // === Anti-cheat (ac) ===
        // Rotate the single-session token every time the take page is rendered
        // for an active attempt. The newest opener wins; any older tab / device
        // still polling with the previous token is locked out by heartbeat().
        $sessionToken = null;
        if ($attempt) {
            $sessionToken = (string) Str::uuid();
            $attempt->forceFill(['session_token' => $sessionToken])->save();
        }

        return view('student.exam-take', compact('exam', 'attempt', 'student', 'sessionToken'));
    }

    /**
     * Start (or resume) an attempt for the exam.
     */
    public function start(Exam $exam): RedirectResponse
    {
        $student = auth()->user();

        $guard = $this->guard($exam, $student);
        if ($guard) {
            return $guard;
        }

        $existing = $exam->studentExams()
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        if (! $existing) {
            $attemptsTaken = $exam->studentExams()
                ->where('student_id', $student->id)
                ->count();

            if ($attemptsTaken >= $exam->attempts_allowed) {
                return redirect()->route('student.exams')
                    ->with('error', 'لقد استنفدت عدد المحاولات المسموح بها لهذا الاختبار.');
            }

            StudentExam::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'started_at' => now(),
                'status' => 'in_progress',
                'attempt_number' => $attemptsTaken + 1,
            ]);
        }

        return redirect()->route('student.exams.show', $exam);
    }

    /**
     * Persist the student's answers, auto-grade, and submit the attempt.
     */
    public function submit(Request $request, Exam $exam): RedirectResponse
    {
        $student = auth()->user();

        $guard = $this->guard($exam, $student);
        if ($guard) {
            return $guard;
        }

        $attempt = $exam->studentExams()
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        if (! $attempt) {
            return redirect()->route('student.exams')
                ->with('error', 'لا توجد محاولة جارية لهذا الاختبار.');
        }

        $answers = (array) $request->input('answers', []);

        DB::transaction(function () use ($exam, $attempt, $answers) {
            foreach ($exam->questions as $question) {
                $value = $answers[$question->id] ?? null;
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $answer = StudentAnswer::updateOrCreate(
                    [
                        'student_exam_id' => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'answer' => $value,
                    ]
                );

                // Auto-grade objective questions; leave essays/short answers for the teacher.
                if ($question->isAutoGradable()) {
                    $answer->autoGrade();
                } else {
                    $answer->update(['marks_obtained' => null, 'is_correct' => null]);
                }
            }

            $attempt->submit();

            // If every question is auto-gradable we can mark the attempt graded now.
            $needsManual = $exam->questions->contains(fn ($q) => ! $q->isAutoGradable());
            if (! $needsManual) {
                $attempt->markAsGraded();
            } else {
                $attempt->calculateScore();
            }
        });

        return redirect()->route('student.exams.result', $exam)
            ->with('status', 'تم تسليم الاختبار بنجاح.');
    }

    /**
     * Fallback number of distinct exit attempts after which the exam auto-ends.
     * Used only when an exam has no explicit per-exam limit configured
     * (see Exam::max_exit_attempts — Trello #229).
     */
    public const AUTO_END_THRESHOLD = 3;

    /**
     * === Anti-cheat (ac) ===
     * Record an exit / focus-loss event sent from the take page (via a
     * keepalive beacon). When the running count crosses the threshold the
     * attempt is auto-submitted. Returns whether the exam was auto-ended.
     */
    public function logExit(Request $request, Exam $exam): JsonResponse
    {
        $student = auth()->user();

        if (! in_array($exam->class_id, $student->enrolledClassIds(), true)) {
            return response()->json(['logged' => false], 403);
        }

        $attempt = $exam->studentExams()
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        // No active attempt → nothing to log (already submitted / never started).
        if (! $attempt) {
            return response()->json(['logged' => false, 'auto_ended' => false]);
        }

        $type = (string) $request->input('type', 'window_blur');
        if (! array_key_exists($type, ExamExitAttempt::TYPES)) {
            $type = 'window_blur';
        }

        $ua = (string) $request->userAgent();
        [$device, $browser] = $this->parseUserAgent($ua);

        $autoEnded = false;
        $threshold = $exam->max_exit_attempts ?: self::AUTO_END_THRESHOLD;

        DB::transaction(function () use ($attempt, $exam, $student, $type, $ua, $device, $browser, $request, $threshold, &$autoEnded) {
            $attempt->increment('exit_attempts_count');
            $count = $attempt->exit_attempts_count;

            $autoEnded = $count >= $threshold && $attempt->submitted_at === null;

            ExamExitAttempt::create([
                'student_exam_id' => $attempt->id,
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'attempt_type' => $type,
                'attempt_count' => $count,
                'auto_ended' => $autoEnded,
                'device' => $device,
                'browser' => $browser,
                'ip_address' => $request->ip(),
                'user_agent' => $ua,
                'occurred_at' => now(),
            ]);

            if ($autoEnded) {
                $this->autoEndAttempt($exam, $attempt);
            }
        });

        return response()->json([
            'logged' => true,
            'count' => $attempt->exit_attempts_count,
            'threshold' => $threshold,
            'auto_ended' => $autoEnded,
        ]);
    }

    /**
     * === Anti-cheat (ac) ===
     * Single-session heartbeat: the take page polls this with the token it was
     * issued. A mismatch means a newer tab / device took over the attempt, so
     * this client must lock itself out.
     */
    public function heartbeat(Request $request, Exam $exam): JsonResponse
    {
        $student = auth()->user();

        $attempt = $exam->studentExams()
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        if (! $attempt) {
            // Attempt was submitted (possibly auto-ended) elsewhere.
            return response()->json(['valid' => false, 'reason' => 'submitted']);
        }

        $token = (string) $request->input('token', '');
        $valid = $token !== '' && hash_equals((string) $attempt->session_token, $token);

        return response()->json([
            'valid' => $valid,
            'reason' => $valid ? null : 'superseded',
        ]);
    }

    /**
     * Auto-submit an attempt that crossed the exit threshold. Grades whatever
     * answers were saved (objective questions auto-grade, the rest stay manual)
     * and flags the row as auto-ended.
     */
    private function autoEndAttempt(Exam $exam, StudentExam $attempt): void
    {
        $exam->loadMissing('questions');

        $attempt->submit();
        $attempt->forceFill(['auto_ended' => true])->save();

        $needsManual = $exam->questions->contains(fn ($q) => ! $q->isAutoGradable());
        if (! $needsManual) {
            $attempt->markAsGraded();
        } else {
            $attempt->calculateScore();
        }
    }

    /**
     * Best-effort device + browser extraction from a user-agent string.
     *
     * @return array{0:string,1:string}
     */
    private function parseUserAgent(string $ua): array
    {
        $device = match (true) {
            (bool) preg_match('/iPad|Tablet/i', $ua) => 'Tablet',
            (bool) preg_match('/Mobile|Android|iPhone/i', $ua) => 'Mobile',
            default => 'Desktop',
        };

        $browser = match (true) {
            (bool) preg_match('/Edg\//i', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/Chrome\//i', $ua) => 'Chrome',
            (bool) preg_match('/Firefox\//i', $ua) => 'Firefox',
            (bool) preg_match('/Safari\//i', $ua) => 'Safari',
            default => 'غير معروف',
        };

        return [$device, $browser];
    }

    /**
     * Show the result of a submitted attempt.
     */
    public function result(Exam $exam): View|RedirectResponse
    {
        $student = auth()->user();

        if (! in_array($exam->class_id, $student->enrolledClassIds(), true)) {
            abort(403, 'هذا الاختبار غير متاح لصفك.');
        }

        $attempt = $exam->studentExams()
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->with(['answers.question', 'exam.subject'])
            ->latest('submitted_at')
            ->first();

        if (! $attempt) {
            return redirect()->route('student.exams.show', $exam);
        }

        return view('student.exam-result', compact('exam', 'attempt', 'student'));
    }

    /**
     * Shared access guard: the exam must belong to the student's class and be
     * open. Returns a redirect/abort when blocked, otherwise null.
     */
    private function guard(Exam $exam, $student): ?RedirectResponse
    {
        if (! in_array($exam->class_id, $student->enrolledClassIds(), true)) {
            abort(403, 'هذا الاختبار غير متاح لصفك.');
        }

        if (! $exam->is_published || $exam->status === 'cancelled') {
            return redirect()->route('student.exams')
                ->with('error', 'هذا الاختبار غير متاح حالياً.');
        }

        $now = now();
        if ($exam->end_time && $now->gt($exam->end_time)) {
            return redirect()->route('student.exams')
                ->with('error', 'انتهى وقت هذا الاختبار.');
        }

        return null;
    }
}
