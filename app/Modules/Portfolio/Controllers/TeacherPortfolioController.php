<?php

namespace App\Modules\Portfolio\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PortfolioEntry;
use App\Modules\Portfolio\Controllers\Concerns\ManagesPortfolioEntries;
use App\Modules\Portfolio\Repositories\Contracts\PortfolioEntryRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Teacher's own "ملف الإنجاز" — mirrors student.portfolio but for a teacher,
 * with the ability to add/edit/delete their own narrative entries.
 */
class TeacherPortfolioController extends Controller
{
    use ManagesPortfolioEntries;

    public function __construct(private PortfolioEntryRepository $entries) {}

    public function portfolio(): View
    {
        $teacher   = auth()->user();
        $sections  = PortfolioEntry::SECTIONS_TEACHER;
        $entryKeys = PortfolioEntry::SECTIONS_TEACHER_ENTRY;
        $entries   = $this->entries->groupedForUser($teacher->id);
        $auto      = $this->teacherAutoData($teacher);

        return view('teacher.portfolio', compact('teacher', 'sections', 'entryKeys', 'entries', 'auto'));
    }

    public function storeEntry(Request $request): RedirectResponse
    {
        $this->saveEntry($request, auth()->user());

        return back()->with('success', 'تم إضافة العنصر بنجاح');
    }

    public function updateEntry(Request $request, PortfolioEntry $entry): RedirectResponse
    {
        abort_unless($entry->user_id === auth()->id(), 404);
        $this->saveEntry($request, auth()->user(), $entry);

        return back()->with('success', 'تم تحديث العنصر بنجاح');
    }

    public function destroyEntry(PortfolioEntry $entry): RedirectResponse
    {
        abort_unless($entry->user_id === auth()->id(), 404);
        $this->deleteEntry($entry);

        return back()->with('success', 'تم حذف العنصر بنجاح');
    }
}
