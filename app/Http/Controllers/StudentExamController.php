<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\StudentAnswer;
use App\Models\StudentExam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return view('student.exam-take', compact('exam', 'attempt', 'student'));
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
