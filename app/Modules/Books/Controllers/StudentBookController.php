<?php

namespace App\Modules\Books\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Books\Repositories\Contracts\BookRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentBookController extends Controller
{
    public function __construct(private BookRepository $books) {}

    public function index(): View
    {
        $user = auth()->user();
        $schoolId = (int) $user->school_id;
        // Try to infer student's grade from their profile; fall back to 0 (will only show grade-null books)
        $gradeLevel = (int) ($user->grade_level ?? data_get($user, 'studentProfile.grade_level') ?? 0);

        $books = $this->books->forStudent($schoolId, $gradeLevel);

        return view('student.books.index', [
            'books' => $books,
            'gradeLevel' => $gradeLevel,
        ]);
    }

    private function accessibleBook(int $id)
    {
        $user = auth()->user();
        $gradeLevel = (int) ($user->grade_level ?? data_get($user, 'studentProfile.grade_level') ?? 0);

        // Same access rules as the list: never reachable outside school/grade.
        $book = $this->books->forStudent((int) $user->school_id, $gradeLevel)->firstWhere('id', $id);
        abort_if(! $book, 404);

        return $book;
    }

    /** In-app digital book reader (card #103). Only books the student may access. */
    public function read(int $id): View
    {
        return view('student.books.read', ['book' => $this->accessibleBook($id)]);
    }

    /** Inline stream for the reader; never forces a download. */
    public function file(int $id)
    {
        $book = $this->accessibleBook($id);
        abort_unless($book->source === 'file' && $book->file_path && Storage::disk('public')->exists($book->file_path), 404);

        return Storage::disk('public')->response($book->file_path, null, ['Content-Disposition' => 'inline']);
    }

    /** Download — blocked server-side when the book disallows it. */
    public function download(int $id)
    {
        $book = $this->accessibleBook($id);
        abort_unless($book->allow_download, 403);
        if ($book->source === 'external_url') {
            return redirect()->away((string) $book->external_url);
        }
        abort_unless($book->file_path && Storage::disk('public')->exists($book->file_path), 404);

        return Storage::disk('public')->download($book->file_path);
    }
}
