<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\StudentExam;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * Route-name prefix for links/redirects — teacher.exams when reached under
     * /teacher, admin.exams otherwise. Lets the shared exam views work from both
     * route groups without the teacher hitting the admin-only CheckRole gate.
     */
    private function routePrefix(): string
    {
        return request()->routeIs('teacher.exams.*') ? 'teacher.exams' : 'admin.exams';
    }

    /**
     * Display a listing of exams.
     */
    public function index(Request $request)
    {
        $query = Exam::with(['teacher', 'subject', 'classRoom', 'academicYear'])
            ->withCount('questions');

        // Filter by teacher for non-admin users
        if (!Auth::user()->hasAnyRole(['super-admin', 'school-admin'])) {
            $query->where('teacher_id', Auth::id());
        }

        $filters = [
            'grade_level' => $request->input('grade_level'),
            'teacher_id'  => $request->input('teacher_id'),
            'subject_id'  => $request->input('subject_id'),
            'class_id'    => $request->input('class_id'),
            'type'        => $request->input('type'),
            'status'      => $request->input('status'),
        ];

        if (! empty($filters['grade_level'])) {
            $gl = $filters['grade_level'];
            $query->whereHas('classRoom', fn ($q) => $q->where('grade_level', $gl));
        }
        if (! empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }
        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (! empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $exams = $query->latest()->paginate(15)->withQueryString();

        // Stats — show the same totals regardless of filters so tiles stay stable.
        $statsBase = Exam::query();
        if (! Auth::user()->hasAnyRole(['super-admin', 'school-admin'])) {
            $statsBase->where('teacher_id', Auth::id());
        }
        $stats = [
            'total'     => (clone $statsBase)->count(),
            'published' => (clone $statsBase)->where('is_published', true)->count(),
            'active'    => (clone $statsBase)->where('status', 'active')->count(),
            'upcoming'  => (clone $statsBase)->where('start_time', '>', now())->count(),
        ];

        $subjects = Subject::orderBy('name')->get();
        $classes  = ClassRoom::orderBy('name')->get();
        $teachers = User::whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))
            ->orderBy('name')->get(['id', 'name']);

        $routePrefix = $this->routePrefix();

        return view('admin.exams.index', compact(
            'exams', 'subjects', 'classes', 'teachers', 'filters', 'stats', 'routePrefix'
        ));
    }

    /**
     * Show the form for creating a new exam.
     */
    public function create()
    {
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassRoom::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $routePrefix = $this->routePrefix();

        return view('admin.exams.create', compact('subjects', 'classes', 'academicYears', 'routePrefix'));
    }

    /**
     * Store a newly created exam.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'type' => 'required|in:quiz,midterm,final,assignment,homework',
            'description' => 'nullable|string',
            'total_marks' => 'required|numeric|min:1',
            'pass_marks' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'attempts_allowed' => 'required|integer|min:1|max:10',
            'max_exit_attempts' => 'required|integer|min:1|max:50',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results' => 'boolean',
        ]);

        $validated['teacher_id'] = Auth::id();
        $validated['shuffle_questions'] = $request->boolean('shuffle_questions');
        $validated['shuffle_answers'] = $request->boolean('shuffle_answers');
        $validated['show_results'] = $request->boolean('show_results');

        $exam = Exam::create($validated);

        return redirect()->route($this->routePrefix() . '.questions.index', $exam)
            ->with('success', 'تم إنشاء الاختبار بنجاح. أضف الأسئلة الآن.');
    }

    /**
     * Display the specified exam.
     */
    public function show(Exam $exam)
    {
        $exam->load(['teacher', 'subject', 'classRoom', 'academicYear', 'questions', 'studentExams.student']);

        $statistics = [
            'total_students' => $exam->studentExams->count(),
            'completed' => $exam->studentExams->where('status', 'graded')->count(),
            'in_progress' => $exam->studentExams->where('status', 'in_progress')->count(),
            'not_started' => $exam->studentExams->where('status', 'not_started')->count(),
            'average_score' => $exam->studentExams->where('status', 'graded')->avg('percentage'),
            'highest_score' => $exam->studentExams->where('status', 'graded')->max('percentage'),
            'lowest_score' => $exam->studentExams->where('status', 'graded')->min('percentage'),
            'pass_rate' => $exam->pass_marks
                ? $exam->studentExams->where('status', 'graded')->where('score', '>=', $exam->pass_marks)->count() / max(1, $exam->studentExams->where('status', 'graded')->count()) * 100
                : null,
        ];

        $routePrefix = $this->routePrefix();

        return view('admin.exams.show', compact('exam', 'statistics', 'routePrefix'));
    }

    /**
     * Show the form for editing the specified exam.
     */
    public function edit(Exam $exam)
    {
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassRoom::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $routePrefix = $this->routePrefix();

        return view('admin.exams.edit', compact('exam', 'subjects', 'classes', 'academicYears', 'routePrefix'));
    }

    /**
     * Update the specified exam.
     */
    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'type' => 'required|in:quiz,midterm,final,assignment,homework',
            'description' => 'nullable|string',
            'total_marks' => 'required|numeric|min:1',
            'pass_marks' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'attempts_allowed' => 'required|integer|min:1|max:10',
            'max_exit_attempts' => 'required|integer|min:1|max:50',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results' => 'boolean',
        ]);

        $validated['shuffle_questions'] = $request->boolean('shuffle_questions');
        $validated['shuffle_answers'] = $request->boolean('shuffle_answers');
        $validated['show_results'] = $request->boolean('show_results');

        $exam->update($validated);

        return redirect()->route($this->routePrefix() . '.show', $exam)
            ->with('success', 'تم تحديث الاختبار بنجاح.');
    }

    /**
     * Remove the specified exam.
     */
    public function destroy(Exam $exam)
    {
        $exam->delete();

        return redirect()->route($this->routePrefix() . '.index')
            ->with('success', 'تم حذف الاختبار بنجاح.');
    }

    /**
     * Publish the exam.
     */
    public function publish(Exam $exam)
    {
        if ($exam->questions()->count() === 0) {
            return back()->with('error', 'لا يمكن نشر اختبار بدون أسئلة.');
        }

        $exam->update([
            'is_published' => true,
            'status' => 'scheduled',
        ]);

        return back()->with('success', 'تم نشر الاختبار بنجاح.');
    }

    /**
     * Unpublish the exam.
     */
    public function unpublish(Exam $exam)
    {
        $exam->update([
            'is_published' => false,
            'status' => 'draft',
        ]);

        return back()->with('success', 'تم إلغاء نشر الاختبار.');
    }

    /**
     * Activate the exam.
     */
    public function activate(Exam $exam)
    {
        $exam->update(['status' => 'active']);

        return back()->with('success', 'تم تفعيل الاختبار.');
    }

    /**
     * Complete the exam.
     */
    public function complete(Exam $exam)
    {
        $exam->update(['status' => 'completed']);

        return back()->with('success', 'تم إنهاء الاختبار.');
    }

    /**
     * Display exam results.
     */
    public function results(Exam $exam)
    {
        $exam->load(['questions', 'studentExams' => function ($query) {
            $query->with(['student', 'answers.question', 'exitAttempts' => function ($q) {
                $q->orderBy('occurred_at');
            }])->orderBy('score', 'desc');
        }]);

        $routePrefix = $this->routePrefix();

        return view('admin.exams.results', compact('exam', 'routePrefix'));
    }

    /**
     * === Anti-cheat card (ac) — Trello #229 ===
     * Re-open a student's attempt that was auto-locked after exceeding the
     * tab-exit limit, so the student can resume / retake. Clears the lock and
     * resets the exit counter; the student's saved answers are preserved.
     *
     * Gated by the `exams.edit` permission (admins + teachers). Teachers may
     * only re-open attempts belonging to their own exams — mirrors how every
     * other exam action is scoped (super-admin / school-admin pass through,
     * otherwise teacher_id must match the authed user).
     */
    public function reopenAttempt(Exam $exam, StudentExam $studentExam)
    {
        if (! Auth::user()->canDo('exams.edit')) {
            abort(403, 'ليس لديك صلاحية إعادة فتح الاختبار.');
        }

        // The studentExam must actually belong to this exam.
        if ($studentExam->exam_id !== $exam->id) {
            abort(404);
        }

        // Ownership: non-admins may only manage their own exams.
        if (! Auth::user()->hasAnyRole(['super-admin', 'school-admin'])
            && $exam->teacher_id !== Auth::id()) {
            abort(403, 'هذا الاختبار لا يخصك.');
        }

        $studentExam->forceFill([
            'submitted_at' => null,
            'auto_ended' => false,
            'status' => 'in_progress',
            'exit_attempts_count' => 0,
            'session_token' => null,
            'started_at' => $studentExam->started_at ?? now(),
        ])->save();

        return back()->with('success', 'تم إعادة فتح الاختبار للطالب. يمكنه الآن استكمال الاختبار.');
    }
}
