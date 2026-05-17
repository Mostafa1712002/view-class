<?php

namespace App\Modules\Books\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Book;
use App\Models\Subject;
use App\Modules\Books\Actions\UploadBookAction;
use App\Modules\Books\Repositories\Contracts\BookRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BookController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private BookRepository $books,
        private UploadBookAction $uploader,
    ) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $filters = $this->extractFilters($request);

        $books = $this->books->paginate($schoolId, $filters);

        return view('admin.books.index', [
            'books' => $books,
            'filters' => $filters,
            'subjects' => $this->subjectsForSchool($schoolId),
            'grades' => $this->gradeList(),
            'terms' => $this->termsList(),
        ]);
    }

    public function create(Request $request): View
    {
        $schoolId = $this->activeSchoolId();

        $book = new Book([
            'source' => Book::SOURCE_FILE,
            'is_active' => true,
            'is_ministry' => (bool) $request->boolean('ministry'),
        ]);

        return view('admin.books.create', [
            'book' => $book,
            'subjects' => $this->subjectsForSchool($schoolId),
            'grades' => $this->gradeList(),
            'terms' => $this->termsList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBook($request);
        $schoolId = $this->activeSchoolId();

        $this->uploader->execute(
            $data,
            $schoolId,
            (int) auth()->id(),
            $request->file('file'),
            $request->file('cover'),
        );

        return redirect()
            ->route('manage.books.index')
            ->with('success', __('books_admin.flash_created'));
    }

    public function edit(int $id): View
    {
        $schoolId = $this->activeSchoolId();
        $book = $this->books->findScoped($id, $schoolId);
        abort_if(!$book, 404);

        return view('admin.books.edit', [
            'book' => $book,
            'subjects' => $this->subjectsForSchool($schoolId),
            'grades' => $this->gradeList(),
            'terms' => $this->termsList(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $book = $this->books->findScoped($id, $schoolId);
        abort_if(!$book, 404);

        $data = $this->validateBook($request, $update = true);
        $this->uploader->update(
            $book,
            $data,
            $request->file('file'),
            $request->file('cover'),
        );

        return redirect()
            ->route('manage.books.index')
            ->with('success', __('books_admin.flash_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $book = $this->books->findScoped($id, $schoolId);
        abort_if(!$book, 404);

        $this->books->delete($book);

        return redirect()
            ->route('manage.books.index')
            ->with('success', __('books_admin.flash_deleted'));
    }

    private function extractFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->get('q', '')),
            'subject_id' => $request->get('subject_id'),
            'grade_level' => $request->get('grade_level'),
            'academic_term_id' => $request->get('academic_term_id'),
            'is_ministry' => $request->get('is_ministry'),
            'is_active' => $request->get('is_active'),
        ];
    }

    private function validateBook(Request $request, bool $update = false): array
    {
        $fileRule = $update
            ? ['nullable', 'file', 'mimes:pdf', 'max:20480']
            : ['nullable', 'file', 'mimes:pdf', 'max:20480'];

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'grade_level' => ['nullable', 'integer', 'min:1', 'max:12'],
            'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'source' => ['required', 'in:file,external_url'],
            'file' => array_merge(
                ['nullable', 'file', 'mimes:pdf', 'max:20480'],
                ($update ? [] : ['required_if:source,file']),
            ),
            'external_url' => ['nullable', 'url', 'required_if:source,external_url', 'max:1024'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_ministry' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function subjectsForSchool(?int $schoolId): Collection
    {
        $query = Subject::query()->select('id', 'name', 'name_en')->where('is_active', true);
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }
        return $query->orderBy('name')->get();
    }

    private function termsList(): Collection
    {
        return AcademicTerm::query()->select('id', 'name')->orderByDesc('is_current')->orderBy('sort_order')->get();
    }

    /** @return array<int,string> */
    private function gradeList(): array
    {
        $labels = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[$i] = __('books_admin.grade_label', ['n' => $i]);
        }
        return $labels;
    }
}
