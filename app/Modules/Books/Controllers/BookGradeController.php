<?php

namespace App\Modules\Books\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Section;
use App\Modules\Books\Actions\SyncSchoolGradeBooksAction;
use App\Modules\Books\Repositories\Contracts\BookRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bulk management of school ↔ grade ↔ book links.
 * Shows every educational stage (Section) of the active school, the grades
 * (ClassRoom) under each, and the available books as checkboxes per grade.
 */
class BookGradeController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private BookRepository $books,
        private SyncSchoolGradeBooksAction $sync,
    ) {}

    public function index(): View
    {
        $schoolId = $this->activeSchoolId();

        if ($schoolId === null) {
            return view('admin.books.grades', [
                'school' => null,
                'stages' => collect(),
                'availableBooks' => collect(),
                'linked' => [],
            ]);
        }

        $stages = Section::query()
            ->where('school_id', $schoolId)
            ->with(['classes' => function ($q) {
                $q->orderBy('grade_level')->orderBy('name');
            }])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('admin.books.grades', [
            'school' => School::find($schoolId),
            'stages' => $stages,
            'availableBooks' => $this->books->availableBooksForSchool($schoolId),
            'linked' => $this->books->linkedBookIdsByClass($schoolId),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        if ($schoolId === null) {
            return redirect()
                ->route('manage.books.grades')
                ->with('error', __('books_admin.grades.no_school'));
        }

        $validated = $request->validate([
            'grades' => ['nullable', 'array'],
            'grades.*' => ['nullable', 'array'],
            'grades.*.*' => ['integer'],
        ]);

        /** @var array<int,int[]> $selection */
        $selection = $validated['grades'] ?? [];

        try {
            $this->sync->execute($schoolId, $selection);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('manage.books.grades')
                ->with('error', __('books_admin.grades.flash_error'));
        }

        return redirect()
            ->route('manage.books.grades')
            ->with('success', __('books_admin.grades.flash_saved'));
    }
}
