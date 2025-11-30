<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * Display a listing of exams.
     */
    public function index(Request $request)
    {
        $query = Exam::with(['teacher', 'subject', 'classRoom', 'academicYear'])
            ->withCount('questions');

        // Filter by teacher for non-admin users
        if (!Auth::user()->hasRole(['super-admin', 'school-admin'])) {
            $query->where('teacher_id', Auth::id());
        }

        // Apply filters
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $exams = $query->latest()->paginate(15);
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassRoom::orderBy('name')->get();

        return view('admin.exams.index', compact('exams', 'subjects', 'classes'));
    }

    /**
     * Show the form for creating a new exam.
     */
    public function create()
    {
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassRoom::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('admin.exams.create', compact('subjects', 'classes', 'academicYears'));
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
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results' => 'boolean',
        ]);

        $validated['teacher_id'] = Auth::id();
        $validated['shuffle_questions'] = $request->boolean('shuffle_questions');
        $validated['shuffle_answers'] = $request->boolean('shuffle_answers');
        $validated['show_results'] = $request->boolean('show_results');

        $exam = Exam::create($validated);

        return redirect()->route('admin.exams.questions.index', $exam)
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

        return view('admin.exams.show', compact('exam', 'statistics'));
    }

    /**
     * Show the form for editing the specified exam.
     */
    public function edit(Exam $exam)
    {
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassRoom::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('admin.exams.edit', compact('exam', 'subjects', 'classes', 'academicYears'));
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
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results' => 'boolean',
        ]);

        $validated['shuffle_questions'] = $request->boolean('shuffle_questions');
        $validated['shuffle_answers'] = $request->boolean('shuffle_answers');
        $validated['show_results'] = $request->boolean('show_results');

        $exam->update($validated);

        return redirect()->route('admin.exams.show', $exam)
            ->with('success', 'تم تحديث الاختبار بنجاح.');
    }

    /**
     * Remove the specified exam.
     */
    public function destroy(Exam $exam)
    {
        $exam->delete();

        return redirect()->route('admin.exams.index')
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
            $query->with(['student', 'answers.question'])->orderBy('score', 'desc');
        }]);

        return view('admin.exams.results', compact('exam'));
    }
}
