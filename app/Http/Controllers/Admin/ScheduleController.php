<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
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

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $schoolId = $this->getSchoolId();

        $query = Schedule::with(['classRoom.section', 'academicYear']);

        if ($schoolId) {
            $query->whereHas('classRoom.section', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
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

        $schedules = $query->latest()->paginate(15);

        // للفلترة
        $classesQuery = ClassRoom::with('section');
        $yearsQuery = AcademicYear::query();

        if ($schoolId) {
            $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
            $yearsQuery->where('school_id', $schoolId);
        }

        $classes = $classesQuery->get();
        $academicYears = $yearsQuery->get();

        return view('admin.schedules.index', compact('schedules', 'classes', 'academicYears'));
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

        // التحقق من عدم وجود جدول مكرر
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

        // تنظيم الحصص في جدول
        $timetable = [];
        $days = SchedulePeriod::DAYS;
        $periodsCount = 7; // عدد الحصص في اليوم

        foreach ($days as $dayNum => $dayName) {
            $timetable[$dayNum] = [];
            for ($period = 1; $period <= $periodsCount; $period++) {
                $timetable[$dayNum][$period] = $schedule->getPeriod($dayNum, $period);
            }
        }

        return view('admin.schedules.show', compact('schedule', 'timetable', 'days', 'periodsCount'));
    }

    public function edit(Schedule $schedule)
    {
        $this->authorizeAccess();
        $schoolId = $this->getSchoolId();

        $schedule->load(['classRoom.section', 'academicYear', 'periods.subject', 'periods.teacher']);

        // المواد والمعلمين
        $subjectsQuery = Subject::query();
        $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));

        if ($schoolId) {
            $subjectsQuery->where('school_id', $schoolId);
            $teachersQuery->where('school_id', $schoolId);
        }

        $subjects = $subjectsQuery->get();
        $teachers = $teachersQuery->get();

        // تنظيم الحصص في جدول
        $timetable = [];
        $days = SchedulePeriod::DAYS;
        $periodsCount = 7;

        foreach ($days as $dayNum => $dayName) {
            $timetable[$dayNum] = [];
            for ($period = 1; $period <= $periodsCount; $period++) {
                $timetable[$dayNum][$period] = $schedule->getPeriod($dayNum, $period);
            }
        }

        return view('admin.schedules.edit', compact('schedule', 'timetable', 'days', 'periodsCount', 'subjects', 'teachers'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
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

        return redirect()->route('manage.schedules.index')
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

        // البحث عن حصة موجودة أو إنشاء جديدة
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

    // حذف حصة
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

    // جدول المعلم
    public function teacherSchedule(Request $request)
    {
        $user = auth()->user();

        if (!$user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403);
        }

        $teacherId = $request->get('teacher_id', $user->id);

        // التحقق من الصلاحية
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin() && $teacherId != $user->id) {
            abort(403);
        }

        $teacher = User::findOrFail($teacherId);

        $periods = SchedulePeriod::with(['schedule.classRoom.section', 'subject'])
            ->where('teacher_id', $teacherId)
            ->whereHas('schedule', fn($q) => $q->active())
            ->get();

        // تنظيم الحصص
        $timetable = [];
        $days = SchedulePeriod::DAYS;
        $periodsCount = 7;

        foreach ($days as $dayNum => $dayName) {
            $timetable[$dayNum] = [];
            for ($period = 1; $period <= $periodsCount; $period++) {
                $timetable[$dayNum][$period] = $periods->first(function ($p) use ($dayNum, $period) {
                    return $p->day_of_week == $dayNum && $p->period_number == $period;
                });
            }
        }

        // قائمة المعلمين للفلترة (للمدراء فقط)
        $teachers = null;
        if ($user->isSuperAdmin() || $user->isSchoolAdmin()) {
            $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));
            if ($user->school_id) {
                $teachersQuery->where('school_id', $user->school_id);
            }
            $teachers = $teachersQuery->get();
        }

        return view('admin.schedules.teacher-schedule', compact('teacher', 'timetable', 'days', 'periodsCount', 'teachers'));
    }
}
