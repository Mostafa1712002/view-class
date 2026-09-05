<?php

namespace App\Modules\Portfolio\Controllers\Concerns;

use App\Http\Controllers\StudentController;
use App\Models\Certificate;
use App\Models\ClassVisit;
use App\Models\PortfolioEntry;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Shared by AdminPortfolioController (any teacher/student) and
 * TeacherPortfolioController (own portfolio only) — entry validation/upload
 * and the auto-derived section data are identical either way.
 *
 * A using class must expose a `PortfolioEntryRepository $entries` property
 * (both controllers inject it via constructor promotion).
 */
trait ManagesPortfolioEntries
{
    private function saveEntry(Request $request, User $owner, ?PortfolioEntry $entry = null): void
    {
        $allowedSections = $owner->isTeacher()
            ? PortfolioEntry::SECTIONS_TEACHER_ENTRY
            : PortfolioEntry::SECTIONS_STUDENT_ENTRY;

        $validated = $request->validate([
            'section'    => ['required', Rule::in($allowedSections)],
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['nullable', 'string', 'max:5000'],
            'entry_date' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip,rar'],
        ]);

        if ($request->hasFile('attachment')) {
            if ($entry?->attachment_path) {
                Storage::disk('public')->delete($entry->attachment_path);
            }
            $validated['attachment_path'] = $request->file('attachment')->store('portfolio-entries', 'public');
        }
        unset($validated['attachment']);

        if ($entry) {
            $this->entries->update($entry, $validated);

            return;
        }

        $validated['user_id']    = $owner->id;
        $validated['created_by'] = auth()->id();
        $this->entries->create($validated);
    }

    private function deleteEntry(PortfolioEntry $entry): void
    {
        if ($entry->attachment_path) {
            Storage::disk('public')->delete($entry->attachment_path);
        }
        $this->entries->delete($entry);
    }

    /** @return array<string, mixed> */
    private function teacherAutoData(User $teacher): array
    {
        $schoolId = $teacher->school_id;

        return [
            'teacher_profile' => $teacher->teacherProfile,
            'achievements'    => Certificate::where('recipient_user_id', $teacher->id)
                ->where('status', 'published')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderByDesc('issue_date')
                ->get(),
            'testimonials' => ClassVisit::where('teacher_id', $teacher->id)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->with('supervisor:id,name')
                ->orderByDesc('visit_date')
                ->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function studentAutoData(User $student): array
    {
        $data                    = app(StudentController::class)->portfolioData($student);
        $data['student_profile'] = StudentProfile::where('user_id', $student->id)->first();
        // Alias to match the 'achievements' key the teacher side uses, so the
        // show/teacher views can read $auto['achievements'] either way.
        $data['achievements']    = $data['certificates'];

        return $data;
    }
}
