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
        $gradeLevel = $this->gradeLevel();

        $books = $this->books->forStudent($schoolId, $gradeLevel);

        return view('student.books.index', [
            'books' => $books,
            'gradeLevel' => $gradeLevel,
        ]);
    }

    /** Student's grade comes from their class (users.class_room_id); 0 = no class, ungraded books only. */
    private function gradeLevel(): int
    {
        return (int) optional(auth()->user()->classRoom)->grade_level;
    }

    private function accessibleBook(int $id)
    {
        // Same access rules as the list: never reachable outside school/grade.
        $book = $this->books->forStudent((int) auth()->user()->school_id, $this->gradeLevel())->firstWhere('id', $id);
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
        $disk = $book->fileDisk();
        abort_unless($disk, 404);

        return Storage::disk($disk)->response($book->file_path, null, ['Content-Disposition' => 'inline']);
    }

    /** Download — blocked server-side when the book disallows it. */
    public function download(int $id)
    {
        $book = $this->accessibleBook($id);
        abort_unless($book->allow_download, 403);
        if ($book->source === 'external_url') {
            return redirect()->away((string) $book->external_url);
        }
        $disk = $book->fileDisk();
        abort_unless($disk, 404);

        return Storage::disk($disk)->download($book->file_path);
    }
}
