<?php

namespace App\Modules\Lessons\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Lessons\Actions\CreateLessonAction;
use App\Modules\Lessons\Actions\UpdateLessonAction;
use App\Modules\Lessons\DTOs\LessonDto;
use App\Modules\Lessons\Http\Requests\LessonRequest;
use App\Modules\Lessons\Repositories\Contracts\LessonRepository;
use Illuminate\Http\Request;
use RuntimeException;

class LessonController extends Controller
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

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $schoolId = $this->schoolId();

        $filters = $request->only(['class_id', 'section_id', 'teacher_id', 'subject_id', 'day_of_week', 'search']);
        $lessons = $this->lessons->paginate($schoolId, $filters, 20);

        [$classes, $sections, $teachers, $subjects] = $this->referenceData($schoolId);

        return view('admin.lessons.index', compact('lessons', 'filters', 'classes', 'sections', 'teachers', 'subjects'));
    }

    public function create()
    {
        $this->authorizeAccess();
        $schoolId = $this->schoolId();

        [$classes, $sections, $teachers, $subjects, $years] = $this->formData($schoolId);

        $lesson = null;
        return view('admin.lessons.form', compact('lesson', 'classes', 'sections', 'teachers', 'subjects', 'years'));
    }

    public function store(LessonRequest $request, CreateLessonAction $action)
    {
        try {
            $lesson = $action->execute(LessonDto::fromArray($request->validated()));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.lessons.index')
            ->with('success', trans('lessons_admin.flash.created'));
    }

    public function edit(int $id)
    {
        $this->authorizeAccess();
        $schoolId = $this->schoolId();

        $lesson = $this->lessons->find($id, $schoolId);
        if (!$lesson) {
            abort(404);
        }

        [$classes, $sections, $teachers, $subjects, $years] = $this->formData($schoolId);

        return view('admin.lessons.form', compact('lesson', 'classes', 'sections', 'teachers', 'subjects', 'years'));
    }

    public function update(LessonRequest $request, UpdateLessonAction $action, int $id)
    {
        $schoolId = $this->schoolId();
        $lesson = $this->lessons->find($id, $schoolId);
        if (!$lesson) {
            abort(404);
        }

        try {
            $action->execute($lesson, LessonDto::fromArray($request->validated()));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.lessons.index')
            ->with('success', trans('lessons_admin.flash.updated'));
    }

    public function destroy(int $id)
    {
        $this->authorizeAccess();
        $schoolId = $this->schoolId();

        $lesson = $this->lessons->find($id, $schoolId);
        if (!$lesson) {
            abort(404);
        }

        $lesson->delete();

        return redirect()
            ->route('admin.lessons.index')
            ->with('success', trans('lessons_admin.flash.deleted'));
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection, 3: \Illuminate\Support\Collection}
     */
    private function referenceData(?int $schoolId): array
    {
        $classesQuery = ClassRoom::with('section');
        $sectionsQuery = Section::query();
        $teachersQuery = User::whereHas('roles', fn ($q) => $q->where('slug', 'teacher'));
        $subjectsQuery = Subject::query();

        if ($schoolId !== null) {
            $classesQuery->whereHas('section', fn ($q) => $q->where('school_id', $schoolId));
            $sectionsQuery->where('school_id', $schoolId);
            $teachersQuery->where('school_id', $schoolId);
            $subjectsQuery->where('school_id', $schoolId);
        }

        return [
            $classesQuery->orderBy('name')->get(),
            $sectionsQuery->orderBy('name')->get(),
            $teachersQuery->orderBy('name')->get(),
            $subjectsQuery->orderBy('name')->get(),
        ];
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection, 3: \Illuminate\Support\Collection, 4: \Illuminate\Support\Collection}
     */
    private function formData(?int $schoolId): array
    {
        [$classes, $sections, $teachers, $subjects] = $this->referenceData($schoolId);

        $yearsQuery = AcademicYear::query();
        if ($schoolId !== null) {
            $yearsQuery->where('school_id', $schoolId);
        }
        $years = $yearsQuery->orderByDesc('id')->get();

        return [$classes, $sections, $teachers, $subjects, $years];
    }
}
