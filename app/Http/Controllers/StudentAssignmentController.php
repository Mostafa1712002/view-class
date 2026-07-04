<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Student-facing assignment detail + submission (card #302).
 *
 * Reached from the subject content hub (student.subjects.show → assignments
 * section). Lets a student open an assignment, read its details, submit a text
 * answer and/or a file, and see the grade + feedback once graded.
 *
 * Security: an assignment is only reachable if it is published, belongs to the
 * student's school, and targets one of the student's enrolled classes.
 */
class StudentAssignmentController extends Controller
{
    public function show(Assignment $assignment): View
    {
        $student = auth()->user();
        $this->authorizeAssignment($assignment, $student);

        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        return view('student.assignments.show', compact('student', 'assignment', 'submission'));
    }

    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $student = auth()->user();
        $this->authorizeAssignment($assignment, $student);

        // Block submissions on closed assignments, or overdue ones that don't
        // allow late submission.
        if ($assignment->status === 'closed'
            || ($assignment->is_overdue && ! $assignment->allow_late_submission)) {
            return back()->with('error', __('subjects_content.assignment_closed'));
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'file'    => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip,rar,txt'],
        ]);

        if (empty($validated['content']) && ! $request->hasFile('file')) {
            return back()->with('error', __('subjects_content.assignment_empty_submission'));
        }

        $submission = AssignmentSubmission::firstOrNew([
            'assignment_id' => $assignment->id,
            'student_id'    => $student->id,
        ]);

        // A graded/returned submission is locked — students don't overwrite feedback.
        if (in_array($submission->status, ['graded', 'returned'], true)) {
            return back()->with('error', __('subjects_content.assignment_already_graded'));
        }

        if ($request->hasFile('file')) {
            // Replace any previous file.
            if ($submission->file_path) {
                Storage::disk('public')->delete($submission->file_path);
            }
            $file = $request->file('file');
            $submission->file_path = $file->store('assignment-submissions', 'public');
            $submission->file_name = $file->getClientOriginalName();
        }

        $submission->content      = $validated['content'] ?? $submission->content;
        $submission->submitted_at = now();
        $submission->is_late      = $assignment->is_overdue;
        $submission->status       = 'submitted';
        $submission->save();

        return redirect()
            ->route('student.assignments.show', $assignment->id)
            ->with('success', __('subjects_content.assignment_submitted'));
    }

    /**
     * A student may only reach a published assignment that belongs to their
     * school and one of their enrolled classes.
     */
    private function authorizeAssignment(Assignment $assignment, $student): void
    {
        abort_unless($assignment->school_id === $student->school_id, 403);
        abort_unless($assignment->status === 'published', 403);
        abort_unless(in_array($assignment->class_id, $student->enrolledClassIds(), true), 403);
    }
}
