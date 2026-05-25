<?php

namespace App\Modules\Lessons\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\SchedulePeriod;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الجدول المتقدم — a visual, day-by-period board view of the lessons
 * (schedule_periods), filterable by class / teacher / subject.
 */
class LessonScheduleBoardController extends Controller
{
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

    public function index(Request $request): View
    {
        $this->authorizeAccess();
        $schoolId = $this->schoolId();

        $query = SchedulePeriod::query()
            ->with(['subject', 'teacher', 'substituteTeacher', 'schedule.classRoom.section']);

        if ($schoolId !== null) {
            $query->whereHas('schedule.classRoom.section', fn ($q) => $q->where('school_id', $schoolId));
        }

        $filters = $request->only(['class_id', 'teacher_id', 'subject_id']);

        if (!empty($filters['class_id'])) {
            $query->whereHas('schedule', fn ($q) => $q->where('class_id', $filters['class_id']));
        }
        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        // Grid keyed by "day-period".
        $lessons = $query->get();
        $grid = $lessons->groupBy(fn ($p) => $p->day_of_week . '-' . $p->period_number);

        // Number of period columns: largest period in the result, min 7.
        $maxPeriod = max(7, (int) $lessons->max('period_number'));

        $classesQuery = ClassRoom::with('section');
        $teachersQuery = User::whereHas('roles', fn ($q) => $q->where('slug', 'teacher'));
        $subjectsQuery = Subject::query();
        if ($schoolId !== null) {
            $classesQuery->whereHas('section', fn ($q) => $q->where('school_id', $schoolId));
            $teachersQuery->where('school_id', $schoolId);
            $subjectsQuery->where('school_id', $schoolId);
        }

        $classes = $classesQuery->orderBy('name')->get();
        $teachers = $teachersQuery->orderBy('name')->get();
        $subjects = $subjectsQuery->orderBy('name')->get();
        $days = trans('lessons_admin.days');

        return view('admin.lessons.advanced', compact(
            'grid', 'maxPeriod', 'classes', 'teachers', 'subjects', 'days', 'filters'
        ));
    }
}
