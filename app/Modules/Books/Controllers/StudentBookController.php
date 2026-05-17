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
}
