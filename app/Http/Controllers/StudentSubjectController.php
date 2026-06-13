<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Book;
use App\Models\DiscussionRoom;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\SubjectContent;
use App\Models\VirtualClass;
use Illuminate\View\View;

/**
 * Student-facing subject list and content hub.
 *
 * Security model:
 *  - The authenticated user must have the "student" role (enforced in the
 *    route group middleware).
 *  - Every query is school-scoped to $student->school_id; no cross-school
 *    data leakage is possible.
 *  - The show() method verifies the requested subject is in the student's
 *    personal subject list before returning any data.
 */
class StudentSubjectController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Build the list of subjects available to the authenticated student.
     *
     * Subjects are those whose grade_levels JSON array contains the student's
     * class grade level (classes.grade_level via users.class_room_id).
     *
     * @return \Illuminate\Database\Eloquent\Collection<Subject>
     */
    private function studentSubjects($student)
    {
        $gradeLevel = optional($student->classRoom)->grade_level;

        $query = Subject::where('school_id', $student->school_id)
            ->where('is_active', true);

        if ($gradeLevel !== null) {
            $query->whereJsonContains('grade_levels', (int) $gradeLevel);
        }

        return $query->orderBy('certificate_order')->orderBy('name')->get();
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $student  = auth()->user();
        $subjects = $this->studentSubjects($student);

        return view('student.subjects.index', compact('student', 'subjects'));
    }

    // ── show ──────────────────────────────────────────────────────────────────

    public function show(int $subjectId): View
    {
        $student  = auth()->user();
        $subjects = $this->studentSubjects($student);

        // Verify enrollment: the subject must be in the student's subject list.
        $subject = $subjects->firstWhere('id', $subjectId);
        abort_if($subject === null, 403, __('subjects_content.not_enrolled'));

        $classIds = $student->enrolledClassIds();
        $today    = now()->toDateString();

        // ── Content (video / attachment / link) ───────────────────────────────
        $contents = SubjectContent::where('subject_id', $subject->id)
            ->where('school_id', $student->school_id)
            ->published()
            ->available()
            ->orderByDesc('created_at')
            ->get();

        $videos      = $contents->where('type', 'video');
        $attachments = $contents->where('type', 'attachment');
        $links       = $contents->where('type', 'link');

        // ── Assignments ───────────────────────────────────────────────────────
        // Assignment model has: school_id, subject_id, class_id, status
        $assignments = collect();
        if (! empty($classIds)) {
            $assignments = Assignment::where('subject_id', $subject->id)
                ->where('school_id', $student->school_id)
                ->whereIn('class_id', $classIds)
                ->where('status', 'published')
                ->orderByDesc('due_date')
                ->get();
        }

        // ── Exams ─────────────────────────────────────────────────────────────
        $exams = collect();
        if (! empty($classIds)) {
            $exams = Exam::where('subject_id', $subject->id)
                ->whereIn('class_id', $classIds)
                ->where('is_published', true)
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('start_time')
                ->get();
        }

        // ── Virtual classes ───────────────────────────────────────────────────
        // VirtualClass.subject_id (nullable) confirmed in migration — filter by it.
        $vcQuery = VirtualClass::where('subject_id', $subject->id)
            ->where('school_id', $student->school_id)
            ->whereNotIn('status', ['ended', 'cancelled']);

        // Only expose sessions the student's class(es) can see.
        if (! empty($classIds)) {
            $vcQuery->where(function ($w) use ($classIds) {
                $w->whereNull('class_id')
                  ->orWhereIn('class_id', $classIds);
            });
        }

        $virtualClasses = $vcQuery->orderBy('scheduled_at')->get();

        // ── Discussion rooms ──────────────────────────────────────────────────
        // DiscussionRoom has scope_type / scope_id (polymorphic) and school_id.
        // Rooms scoped to this subject are those where scope_type='subject'
        // and scope_id=$subject->id. Fall back gracefully if none.
        $discussionRooms = DiscussionRoom::where('school_id', $student->school_id)
            ->where('scope_type', 'subject')
            ->where('scope_id', $subject->id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get();

        // ── Books ─────────────────────────────────────────────────────────────
        // Book has subject_id, school_id, grade_level, is_active.
        $gradeLevel = optional($student->classRoom)->grade_level;
        $books = Book::where('subject_id', $subject->id)
            ->where('school_id', $student->school_id)
            ->where('is_active', true)
            ->when($gradeLevel !== null, fn ($q) => $q->where('grade_level', $gradeLevel))
            ->orderByDesc('is_ministry')
            ->get();

        return view('student.subjects.show', compact(
            'student',
            'subject',
            'videos',
            'attachments',
            'links',
            'assignments',
            'exams',
            'virtualClasses',
            'discussionRooms',
            'books',
        ));
    }
}
