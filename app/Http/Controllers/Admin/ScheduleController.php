<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\WeeklyPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScheduleController extends Controller
{
    /** Periods per day grid */
    private const PERIODS_PER_DAY = 7;

    /** Days shown in KSA week (Sun-Thu) */
    private const ACTIVE_DAYS = [0, 1, 2, 3, 4];

    protected function authorizeAccess()
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403, 'غير مصرح لك بالوصول');
        }
    }

    protected function getSchoolId()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? null : $user->school_id;
    }

    /**
     * Main schedule page — global weekly grid (defaults to per-class view).
     * Toggle to per-teacher view via ?view=teacher.
     * Sub-route /manage/schedules/list shows the schedule-record list.
     */
    public function index(Request $request)
    {
        $this->authorizeAccess();
        $schoolId = $this->getSchoolId();

        $view = $request->get('view', 'class'); // class|teacher
        if (!in_array($view, ['class', 'teacher'], true)) {
            $view = 'class';
        }

        // Filter values
        $filters = [
            'section_id' => $request->input('section_id'),
            'class_id' => $request->input('class_id'),
            'teacher_id' => $request->input('teacher_id'),
            'subject_id' => $request->input('subject_id'),
            'student_id' => $request->input('student_id'),
            'academic_year_id' => $request->input('academic_year_id'),
            'semester' => $request->input('semester'),
        ];

        // Build period query, school-scoped through schedule->classRoom->section
        $periodQuery = SchedulePeriod::query()
            ->with([
                'subject',
                'teacher',
                'schedule.classRoom.section',
                'schedule.academicYear',
            ])
            ->whereHas('schedule', function ($q) use ($schoolId, $filters) {
                $q->where('is_active', true);
                if ($schoolId) {
                    $q->whereHas('classRoom.section', fn($qq) => $qq->where('school_id', $schoolId));
                }
                if (!empty($filters['class_id'])) {
                    $q->where('class_id', $filters['class_id']);
                }
                if (!empty($filters['section_id'])) {
                    $q->whereHas('classRoom', fn($qq) => $qq->where('section_id', $filters['section_id']));
                }
                if (!empty($filters['academic_year_id'])) {
                    $q->where('academic_year_id', $filters['academic_year_id']);
                }
                if (!empty($filters['semester'])) {
                    $q->where('semester', $filters['semester']);
                }
            });

        if (!empty($filters['teacher_id'])) {
            $periodQuery->where('teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['subject_id'])) {
            $periodQuery->where('subject_id', $filters['subject_id']);
        }
        if (!empty($filters['student_id'])) {
            // Student is linked to classes via class_student pivot; restrict periods
            // to schedules whose class_id is in the student's enrolled classes.
            $classIds = DB::table('class_student')
                ->where('student_id', $filters['student_id'])
                ->pluck('class_id')
                ->all();
            if (empty($classIds)) {
                $periodQuery->whereRaw('1=0');
            } else {
                $periodQuery->whereHas('schedule', fn($q) => $q->whereIn('class_id', $classIds));
            }
        }

        $periods = $periodQuery->get();

        // Build timetable: timetable[day][period_number] = collection of periods.
        // In per-class view, group by class; in per-teacher view, group by teacher.
        $groupedTimetable = [];
        if ($view === 'teacher') {
            // group by teacher_id then by day then by period_number
            $groups = $periods->groupBy('teacher_id');
            foreach ($groups as $teacherId => $list) {
                if (!$teacherId) continue;
                $teacher = $list->first()->teacher;
                if (!$teacher) continue;
                $grid = $this->emptyGrid();
                foreach ($list as $p) {
                    $grid[$p->day_of_week][$p->period_number][] = $p;
                }
                $groupedTimetable[] = [
                    'group_id' => $teacherId,
                    'group_label' => $teacher->name,
                    'grid' => $grid,
                    'count' => $list->count(),
                    'meta' => null,
                ];
            }
            usort($groupedTimetable, fn($a, $b) => strcmp((string) $a['group_label'], (string) $b['group_label']));
        } else {
            // class view
            $groups = $periods->groupBy(fn($p) => optional($p->schedule)->class_id);
            foreach ($groups as $classId => $list) {
                if (!$classId) continue;
                $schedule = $list->first()->schedule;
                if (!$schedule || !$schedule->classRoom) continue;
                $grid = $this->emptyGrid();
                foreach ($list as $p) {
                    $grid[$p->day_of_week][$p->period_number][] = $p;
                }
                $groupedTimetable[] = [
                    'group_id' => $classId,
                    'group_label' => trim(($schedule->classRoom->name ?? '') . ' - ' . ($schedule->classRoom->division ?? '')),
                    'sub_label' => optional($schedule->classRoom->section)->name,
                    'schedule_id' => $schedule->id,
                    'grid' => $grid,
                    'count' => $list->count(),
                    'meta' => [
                        'semester' => $schedule->semester_label,
                        'academic_year' => optional($schedule->academicYear)->name,
                    ],
                ];
            }
            usort($groupedTimetable, fn($a, $b) => strcmp((string) $a['group_label'], (string) $b['group_label']));
        }

        // Derived status indicators (overall, based on current filter scope)
        $statusFlags = $this->computeStatusFlags($filters, $schoolId, $periods);

        // Filter options
        $optionData = $this->filterOptions($schoolId);

        $totals = [
            'schedules' => $periods->pluck('schedule_id')->unique()->count(),
            'periods' => $periods->count(),
            'teachers' => $periods->pluck('teacher_id')->unique()->count(),
            'classes' => $periods->pluck('schedule.class_id')->unique()->count(),
        ];

        $days = $this->activeDaysLabels();

        return view('admin.schedules.index', array_merge($optionData, [
            'groupedTimetable' => $groupedTimetable,
            'filters' => $filters,
            'view' => $view,
            'days' => $days,
            'periodsCount' => self::PERIODS_PER_DAY,
            'totals' => $totals,
            'statusFlags' => $statusFlags,
        ]));
    }

    /**
     * List of underlying Schedule records (CRUD).
     */
    public function manageList(Request $request)
    {
        $this->authorizeAccess();
        $schoolId = $this->getSchoolId();

        $query = Schedule::with(['classRoom.section', 'academicYear'])
            ->withCount('periods');

        if ($schoolId) {
            $query->whereHas('classRoom.section', fn($q) => $q->where('school_id', $schoolId));
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        $schedules = $query->latest()->paginate(20)->withQueryString();

        $optionData = $this->filterOptions($schoolId);

        return view('admin.schedules.manage', array_merge($optionData, [
            'schedules' => $schedules,
        ]));
    }

    public function create()
    {
        $this->authorizeAccess();
        $schoolId = $this->getSchoolId();

        $classesQuery = ClassRoom::with('section')->active();
        $yearsQuery = AcademicYear::query();

        if ($schoolId) {
            $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
            $yearsQuery->where('school_id', $schoolId);
        }

        $classes = $classesQuery->get();
        $academicYears = $yearsQuery->get();

        return view('admin.schedules.create', compact('classes', 'academicYears'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:first,second',
        ], [
            'class_id.required' => 'الفصل مطلوب',
            'class_id.exists' => 'الفصل غير موجود',
            'academic_year_id.required' => 'السنة الدراسية مطلوبة',
            'academic_year_id.exists' => 'السنة الدراسية غير موجودة',
            'semester.required' => 'الفصل الدراسي مطلوب',
            'semester.in' => 'الفصل الدراسي غير صحيح',
        ]);

        $exists = Schedule::where('class_id', $validated['class_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'يوجد جدول بنفس البيانات مسبقاً');
        }

        $schedule = Schedule::create($validated);

        return redirect()->route('manage.schedules.edit', $schedule)
            ->with('success', 'تم إنشاء الجدول بنجاح، يمكنك الآن إضافة الحصص');
    }

    public function show(Schedule $schedule)
    {
        $this->authorizeAccess();

        $schedule->load(['classRoom.section', 'academicYear', 'periods.subject', 'periods.teacher']);

        $timetable = $this->buildClassTimetable($schedule);
        $days = $this->activeDaysLabels();
        $periodsCount = self::PERIODS_PER_DAY;

        return view('admin.schedules.show', compact('schedule', 'timetable', 'days', 'periodsCount'));
    }

    public function edit(Schedule $schedule)
    {
        $this->authorizeAccess();
        $schoolId = $this->getSchoolId();

        $schedule->load(['classRoom.section', 'academicYear', 'periods.subject', 'periods.teacher']);

        $subjectsQuery = Subject::query();
        $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));

        if ($schoolId) {
            $subjectsQuery->where('school_id', $schoolId);
            $teachersQuery->where('school_id', $schoolId);
        }

        $subjects = $subjectsQuery->get();
        $teachers = $teachersQuery->get();

        $timetable = $this->buildClassTimetable($schedule);
        $days = $this->activeDaysLabels();
        $periodsCount = self::PERIODS_PER_DAY;

        return view('admin.schedules.edit', compact('schedule', 'timetable', 'days', 'periodsCount', 'subjects', 'teachers'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $this->authorizeAccess();

        $request->validate([
            'is_active' => 'boolean',
        ]);

        $schedule->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'تم تحديث الجدول بنجاح');
    }

    public function destroy(Schedule $schedule)
    {
        $this->authorizeAccess();

        $schedule->delete();

        return redirect()->route('manage.schedules.list')
            ->with('success', 'تم حذف الجدول بنجاح');
    }

    // إضافة/تعديل حصة
    public function storePeriod(Request $request, Schedule $schedule)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'period_number' => 'required|integer|between:1,7',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
        ], [
            'day_of_week.required' => 'اليوم مطلوب',
            'period_number.required' => 'رقم الحصة مطلوب',
            'subject_id.required' => 'المادة مطلوبة',
            'teacher_id.required' => 'المعلم مطلوب',
            'end_time.after' => 'وقت النهاية يجب أن يكون بعد وقت البداية',
        ]);

        $validated['schedule_id'] = $schedule->id;

        $period = SchedulePeriod::updateOrCreate(
            [
                'schedule_id' => $schedule->id,
                'day_of_week' => $validated['day_of_week'],
                'period_number' => $validated['period_number'],
            ],
            $validated
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الحصة بنجاح',
                'period' => $period->load(['subject', 'teacher']),
            ]);
        }

        return back()->with('success', 'تم حفظ الحصة بنجاح');
    }

    public function destroyPeriod(Schedule $schedule, SchedulePeriod $period)
    {
        $this->authorizeAccess();

        if ($period->schedule_id !== $schedule->id) {
            abort(404);
        }

        $period->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الحصة بنجاح',
            ]);
        }

        return back()->with('success', 'تم حذف الحصة بنجاح');
    }

    public function teacherSchedule(Request $request)
    {
        $user = auth()->user();

        if (!$user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403);
        }

        $teacherId = $request->get('teacher_id', $user->id);

        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin() && $teacherId != $user->id) {
            abort(403);
        }

        $teacher = User::findOrFail($teacherId);

        $periods = SchedulePeriod::with(['schedule.classRoom.section', 'subject'])
            ->where('teacher_id', $teacherId)
            ->whereHas('schedule', fn($q) => $q->active())
            ->get();

        $timetable = $this->emptyGrid();
        foreach ($periods as $p) {
            $timetable[$p->day_of_week][$p->period_number][] = $p;
        }

        $teachers = null;
        if ($user->isSuperAdmin() || $user->isSchoolAdmin()) {
            $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));
            if ($user->school_id) {
                $teachersQuery->where('school_id', $user->school_id);
            }
            $teachers = $teachersQuery->get();
        }

        $days = $this->activeDaysLabels();
        $periodsCount = self::PERIODS_PER_DAY;

        return view('admin.schedules.teacher-schedule', compact('teacher', 'timetable', 'days', 'periodsCount', 'teachers'));
    }

    // ---------- helpers ----------

    private function emptyGrid(): array
    {
        $grid = [];
        foreach (self::ACTIVE_DAYS as $d) {
            $grid[$d] = [];
            for ($p = 1; $p <= self::PERIODS_PER_DAY; $p++) {
                $grid[$d][$p] = [];
            }
        }
        return $grid;
    }

    private function activeDaysLabels(): array
    {
        $names = trans('schedule.days');
        if (!is_array($names)) {
            $names = SchedulePeriod::DAYS;
        }
        $out = [];
        foreach (self::ACTIVE_DAYS as $d) {
            $out[$d] = $names[$d] ?? ('Day ' . $d);
        }
        return $out;
    }

    /**
     * Build a [day][period] => SchedulePeriod|null structure for a single class schedule.
     */
    private function buildClassTimetable(Schedule $schedule): array
    {
        $timetable = [];
        foreach (self::ACTIVE_DAYS as $dayNum) {
            $timetable[$dayNum] = [];
            for ($period = 1; $period <= self::PERIODS_PER_DAY; $period++) {
                $timetable[$dayNum][$period] = $schedule->getPeriod($dayNum, $period);
            }
        }
        return $timetable;
    }

    private function filterOptions($schoolId): array
    {
        $classesQuery = ClassRoom::with('section');
        $sectionsQuery = Section::query();
        $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));
        $subjectsQuery = Subject::query();
        $studentsQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'student'));
        $yearsQuery = AcademicYear::query();

        if ($schoolId) {
            $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
            $sectionsQuery->where('school_id', $schoolId);
            $teachersQuery->where('school_id', $schoolId);
            $subjectsQuery->where('school_id', $schoolId);
            $studentsQuery->where('school_id', $schoolId);
            $yearsQuery->where('school_id', $schoolId);
        }

        return [
            'classes' => $classesQuery->orderBy('name')->get(),
            'sections' => $sectionsQuery->orderBy('name')->get(),
            'teachers' => $teachersQuery->orderBy('name')->get(),
            'subjects' => $subjectsQuery->orderBy('name')->get(),
            'students' => $studentsQuery->orderBy('name')->limit(500)->get(),
            'academicYears' => $yearsQuery->orderBy('name', 'desc')->get(),
        ];
    }

    /**
     * Aggregate availability of related data so the UI can show status badges.
     */
    private function computeStatusFlags(array $filters, $schoolId, $periods): array
    {
        $flags = [
            'weekly_plan' => false,
            'attendance' => false,
            'lesson_prep' => false,
        ];

        // Weekly plans presence per scope
        if (Schema::hasTable('weekly_plans')) {
            $wp = WeeklyPlan::query();
            if ($schoolId) {
                $wp->whereHas('classRoom.section', fn($q) => $q->where('school_id', $schoolId));
            }
            if (!empty($filters['class_id'])) $wp->where('class_id', $filters['class_id']);
            if (!empty($filters['teacher_id'])) $wp->where('teacher_id', $filters['teacher_id']);
            if (!empty($filters['subject_id'])) $wp->where('subject_id', $filters['subject_id']);
            $flags['weekly_plan'] = $wp->exists();
        }

        // Attendance presence per scope
        if (Schema::hasTable('attendances')) {
            $at = Attendance::query();
            if ($schoolId) {
                $at->whereHas('classRoom.section', fn($q) => $q->where('school_id', $schoolId));
            }
            if (!empty($filters['class_id'])) $at->where('class_id', $filters['class_id']);
            if (!empty($filters['teacher_id'])) $at->where('teacher_id', $filters['teacher_id']);
            if (!empty($filters['subject_id'])) $at->where('subject_id', $filters['subject_id']);
            $flags['attendance'] = $at->exists();
        }

        // Lesson preparation table — not present in current schema; remain false.
        // This intentionally renders the badge in neutral state until that module ships.
        $flags['lesson_prep'] = $flags['weekly_plan']; // approximate from weekly plan presence

        return $flags;
    }
}
