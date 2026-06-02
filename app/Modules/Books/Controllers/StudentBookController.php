<?php

namespace App\Modules\Books\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Books\Repositories\Contracts\BookRepository;
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

    /** In-app digital book reader (card #103). Only books the student may access. */
    public function read(int $id): View
    {
        $user = auth()->user();
        $schoolId = (int) $user->school_id;
        $gradeLevel = (int) ($user->grade_level ?? data_get($user, 'studentProfile.grade_level') ?? 0);

        // Reuse the same access rules as the list so a student can never read
        // a book outside their school/grade by guessing the id.
        $book = $this->books->forStudent($schoolId, $gradeLevel)->firstWhere('id', $id);

        abort_if(! $book, 404);

        return view('student.books.read', ['book' => $book]);
    }
}
