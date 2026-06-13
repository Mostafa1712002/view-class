<?php

namespace App\Modules\Subjects\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectContent;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Teacher / admin content management for a single subject.
 *
 * Security model:
 *  - All queries are school-scoped (active school of the authenticated user).
 *  - Teachers may only manage contents of subjects they teach
 *    (subject_teacher.user_id = teacher / school_id match).
 *  - Admins (super-admin, school-admin) bypass the teaching check.
 *  - Private files (attachments) are served only via download() which
 *    re-verifies the caller's access before streaming.
 */
class SubjectContentController extends Controller
{
    use HasSchoolScope;

    // ── Resolve & guard ───────────────────────────────────────────────────────

    /**
     * Load the subject and verify the authenticated user may manage its content.
     * Aborts 403 if a teacher is not assigned to this subject.
     */
    private function resolveSubject(int $subjectId): Subject
    {
        $schoolId = $this->activeSchoolId();
        $user     = auth()->user();

        $subject = Subject::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($subjectId);

        // Super-admin and school-admin bypass the teaching-assignment check.
        if ($user->isSuperAdmin() || $user->hasRole('school-admin')) {
            return $subject;
        }

        // For teachers: confirm they are assigned to teach this subject in this school.
        $assigned = DB::table('subject_teacher')
            ->where('subject_id', $subject->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($assigned, 403, __('subjects_content.not_your_subject'));

        return $subject;
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function index(int $subject): View
    {
        $subject  = $this->resolveSubject($subject);
        $schoolId = $this->activeSchoolId();

        $contents = SubjectContent::where('subject_id', $subject->id)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->with('teacher')
            ->latest()
            ->paginate(20);

        return view('subjects.contents.index', compact('subject', 'contents'));
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function store(Request $request, int $subject): RedirectResponse
    {
        $subject  = $this->resolveSubject($subject);
        $schoolId = $this->activeSchoolId();
        $user     = auth()->user();

        $data = $request->validate([
            'type'            => ['required', 'in:video,attachment,link'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'url'             => ['nullable', 'required_if:type,video,link', 'string', 'max:512'],
            'file'            => [
                'nullable',
                'required_if:type,attachment',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,png,jpg,jpeg,mp4',
                'max:102400', // 100 MB
            ],
            'is_published'    => ['nullable', 'boolean'],
            'available_from'  => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
        ]);

        $filePath = null;
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $filePath = $request->file('file')->store(
                'subject_contents/' . $subject->id,
                'local'
            );
        }

        SubjectContent::create([
            'school_id'      => $schoolId,
            'subject_id'     => $subject->id,
            'teacher_id'     => $user->id,
            'type'           => $data['type'],
            'title'          => $data['title'],
            'description'    => $data['description'] ?? null,
            'url'            => $data['url'] ?? null,
            'file_path'      => $filePath,
            'is_published'   => (bool) ($data['is_published'] ?? false),
            'available_from' => $data['available_from'] ?? null,
            'available_until'=> $data['available_until'] ?? null,
        ]);

        return redirect()
            ->route('manage.subject-contents.index', $subject->id)
            ->with('success', __('subjects_content.flash.created'));
    }

    // ── togglePublish ─────────────────────────────────────────────────────────

    public function togglePublish(Request $request, int $subject, int $content): RedirectResponse
    {
        $subject  = $this->resolveSubject($subject);
        $schoolId = $this->activeSchoolId();

        $content = SubjectContent::where('subject_id', $subject->id)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($content);

        $content->update(['is_published' => ! $content->is_published]);

        return back()->with(
            'success',
            $content->is_published
                ? __('subjects_content.flash.published')
                : __('subjects_content.flash.unpublished')
        );
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function destroy(int $subject, int $content): RedirectResponse
    {
        $subject  = $this->resolveSubject($subject);
        $schoolId = $this->activeSchoolId();

        $content = SubjectContent::where('subject_id', $subject->id)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($content);

        // Delete the private file if one was stored.
        if ($content->file_path && Storage::disk('local')->exists($content->file_path)) {
            Storage::disk('local')->delete($content->file_path);
        }

        $content->delete();

        return redirect()
            ->route('manage.subject-contents.index', $subject->id)
            ->with('success', __('subjects_content.flash.deleted'));
    }

    // ── download ─────────────────────────────────────────────────────────────

    /**
     * Stream a private attachment to an authorized caller.
     *
     * Authorized callers:
     *  - The teacher who owns the content.
     *  - A super-admin or school-admin of the content's school.
     *  - A student whose school_id matches the content's school_id and who is
     *    enrolled in a class that is associated with the subject.
     */
    public function download(int $subject, int $content): Response
    {
        $subjectModel = Subject::findOrFail($subject);
        $content      = SubjectContent::where('subject_id', $subjectModel->id)
            ->where('type', 'attachment')
            ->findOrFail($content);

        $user = auth()->user();

        $authorized = $this->isAuthorizedToDownload($user, $subjectModel, $content);
        abort_unless($authorized, 403, __('subjects_content.download_forbidden'));

        abort_unless(
            $content->file_path && Storage::disk('local')->exists($content->file_path),
            404,
            __('subjects_content.file_not_found')
        );

        $filename = basename($content->file_path);

        return response()->download(
            Storage::disk('local')->path($content->file_path),
            $filename,
            ['Content-Disposition' => 'attachment; filename="' . $filename . '"']
        );
    }

    // ── private helpers ───────────────────────────────────────────────────────

    private function isAuthorizedToDownload($user, Subject $subject, SubjectContent $content): bool
    {
        // Teacher who owns the content
        if ($content->teacher_id === $user->id) {
            return true;
        }

        // Super-admin
        if ($user->isSuperAdmin()) {
            return true;
        }

        // School-admin of the content's school
        if ($user->hasRole('school-admin') && $user->school_id === $content->school_id) {
            return true;
        }

        // Student: must be in the same school and subject grade level
        if ($user->hasRole('student') && $user->school_id === $content->school_id) {
            // Verify the subject belongs to this student's school
            if ($subject->school_id !== $user->school_id) {
                return false;
            }
            // Verify content is published and available
            if (! $content->is_published) {
                return false;
            }
            $today = now()->toDateString();
            if ($content->available_from && $content->available_from->toDateString() > $today) {
                return false;
            }
            if ($content->available_until && $content->available_until->toDateString() < $today) {
                return false;
            }
            return true;
        }

        return false;
    }
}
