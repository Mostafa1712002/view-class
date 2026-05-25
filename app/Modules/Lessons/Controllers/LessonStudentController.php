<?php

namespace App\Modules\Lessons\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Lessons\Repositories\Contracts\LessonRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * إدارة الطلاب داخل الحصة — link / unlink specific students to a lesson
 * so support or enrichment lessons can target a subset of the class.
 */
class LessonStudentController extends Controller
{
    public function __construct(private LessonRepository $lessons) {}

    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isSchoolAdmin())) {
            abort(403, trans('lessons_admin.errors.unauthorized'));
        }
    }

    protected function schoolId(): ?int
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? null : $user->school_id;
    }

    public function index(int $id): View
    {
        $this->authorizeAccess();
        $lesson = $this->lessons->find($id, $this->schoolId());
        abort_if(!$lesson, 404);

        $classRoom = optional($lesson->schedule)->classRoom;
        // Students enrolled in the lesson's classroom are the candidate pool.
        $classStudents = $classRoom ? $classRoom->students()->orderBy('name')->get() : collect();
        $linkedIds = $lesson->students()->pluck('users.id')->all();

        return view('admin.lessons.students', compact('lesson', 'classRoom', 'classStudents', 'linkedIds'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAccess();
        $lesson = $this->lessons->find($id, $this->schoolId());
        abort_if(!$lesson, 404);

        $data = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ids = $data['student_ids'] ?? [];

        // Restrict to students that belong to the lesson's classroom.
        $classRoom = optional($lesson->schedule)->classRoom;
        if ($classRoom) {
            $allowed = $classRoom->students()->pluck('users.id')->all();
            $ids = array_values(array_intersect($ids, $allowed));
        }

        $lesson->students()->sync($ids);

        return redirect()
            ->route('admin.lessons.students.index', $lesson->id)
            ->with('success', 'تم تحديث طلاب الحصة بنجاح');
    }
}
