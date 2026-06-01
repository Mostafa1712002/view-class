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

    public function index(Request $request): View
    {
        $schoolId = $this->resolveBookSchoolId($request);

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
        $schoolId = $this->resolveBookSchoolId($request);
        $backParams = $request->integer('school') ? ['school' => $schoolId] : [];

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
                ->route('manage.books.grades', $backParams)
                ->with('error', __('books_admin.grades.flash_error'));
        }

        return redirect()
            ->route('manage.books.grades', $backParams)
            ->with('success', __('books_admin.grades.flash_saved'));
    }

    /**
     * Resolve the school for the books-per-grade screen. Honors an optional
     * `?school=<id>` override (used by the per-school grade-levels page) when
     * the user is a super-admin or owns that school; otherwise falls back to
     * the active navbar scope.
     */
    private function resolveBookSchoolId(Request $request): ?int
    {
        $requested = $request->integer('school') ?: null;
        if ($requested !== null) {
            $u = auth()->user();
            if ($u && ($u->isSuperAdmin() || (int) $u->school_id === $requested)) {
                return $requested;
            }
        }

        return $this->activeSchoolId();
    }
}
