<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Assignment::with(['teacher', 'subject', 'classRoom', 'academicYear']);

        if (!$user->isSuperAdmin()) {
            $query->where('school_id', $user->school_id);
        }

        if ($user->isTeacher()) {
            $query->where('teacher_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        $assignments = $query->latest()->paginate(20)->withQueryString();

        $subjects = Subject::when(!$user->isSuperAdmin(), fn($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')
            ->get();

        $classes = ClassRoom::when(!$user->isSuperAdmin(), function ($q) use ($user) {
            $q->whereHas('section', fn($sq) => $sq->where('school_id', $user->school_id));
        })->with('section')->orderBy('name')->get();

        return view('admin.assignments.index', compact('assignments', 'subjects', 'classes'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $subjects = Subject::when(!$user->isSuperAdmin(), fn($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')
            ->get();

        $classes = ClassRoom::when(!$user->isSuperAdmin(), function ($q) use ($user) {
            $q->whereHas('section', fn($sq) => $sq->where('school_id', $user->school_id));
        })->with('section')->orderBy('name')->get();

        $academicYear = AcademicYear::where('is_current', true)->first();

        return view('admin.assignments.create', compact('subjects', 'classes', 'academicYear'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'max_score' => 'required|numeric|min:1|max:1000',
            'due_date' => 'required|date|after:today',
            'due_time' => 'nullable|date_format:H:i',
            'allow_late_submission' => 'boolean',
            'late_penalty_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:draft,published',
        ]);

        $academicYear = AcademicYear::where('is_current', true)->first();

        Assignment::create([
            'school_id' => auth()->user()->school_id,
            'teacher_id' => auth()->id(),
            'subject_id' => $request->subject_id,
            'class_id' => $request->class_id,
            'academic_year_id' => $academicYear?->id,
            'title' => $request->title,
            'description' => $request->description,
            'instructions' => $request->instructions,
            'max_score' => $request->max_score,
            'due_date' => $request->due_date,
            'due_time' => $request->due_time,
            'allow_late_submission' => $request->boolean('allow_late_submission'),
            'late_penalty_percent' => $request->late_penalty_percent ?? 0,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'تم إنشاء الواجب بنجاح');
    }

    public function show(Assignment $assignment): View
    {
        $this->authorize($assignment);

        $assignment->load(['teacher', 'subject', 'classRoom', 'academicYear']);

        $students = User::whereHas('classEnrollments', function ($q) use ($assignment) {
            $q->where('class_id', $assignment->class_id)
                ->where('academic_year_id', $assignment->academic_year_id);
        })->get();

        $submissions = $assignment->submissions()
            ->with('student')
            ->get()
            ->keyBy('student_id');

        return view('admin.assignments.show', compact('assignment', 'students', 'submissions'));
    }

    public function edit(Assignment $assignment): View
    {
        $this->authorize($assignment);

        $user = auth()->user();

        $subjects = Subject::when(!$user->isSuperAdmin(), fn($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')
            ->get();

        $classes = ClassRoom::when(!$user->isSuperAdmin(), function ($q) use ($user) {
            $q->whereHas('section', fn($sq) => $sq->where('school_id', $user->school_id));
        })->with('section')->orderBy('name')->get();

        return view('admin.assignments.edit', compact('assignment', 'subjects', 'classes'));
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize($assignment);

        $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'max_score' => 'required|numeric|min:1|max:1000',
            'due_date' => 'required|date',
            'due_time' => 'nullable|date_format:H:i',
            'allow_late_submission' => 'boolean',
            'late_penalty_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:draft,published,closed',
        ]);

        $assignment->update([
            'title' => $request->title,
            'subject_id' => $request->subject_id,
            'class_id' => $request->class_id,
            'description' => $request->description,
            'instructions' => $request->instructions,
            'max_score' => $request->max_score,
            'due_date' => $request->due_date,
            'due_time' => $request->due_time,
            'allow_late_submission' => $request->boolean('allow_late_submission'),
            'late_penalty_percent' => $request->late_penalty_percent ?? 0,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'تم تحديث الواجب بنجاح');
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorize($assignment);

        $assignment->delete();

        return redirect()->route('admin.assignments.index')
            ->with('success', 'تم حذف الواجب بنجاح');
    }

    public function grade(Request $request, Assignment $assignment, User $student): RedirectResponse
    {
        $this->authorize($assignment);

        $request->validate([
            'score' => 'required|numeric|min:0|max:' . $assignment->max_score,
            'feedback' => 'nullable|string|max:1000',
        ]);

        $submission = AssignmentSubmission::firstOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'status' => 'pending',
            ]
        );

        $submission->update([
            'score' => $request->score,
            'feedback' => $request->feedback,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
            'status' => 'graded',
        ]);

        return back()->with('success', 'تم تقييم الطالب بنجاح');
    }

    private function authorize(Assignment $assignment): void
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && $assignment->school_id !== $user->school_id) {
            abort(403);
        }

        if ($user->isTeacher() && $assignment->teacher_id !== $user->id) {
            abort(403);
        }
    }
}
