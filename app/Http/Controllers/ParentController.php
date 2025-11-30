<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentController extends Controller
{
    /**
     * Parent dashboard with all children overview
     */
    public function dashboard(): View
    {
        $parent = auth()->user();
        $children = $parent->children()->with(['enrolledClasses', 'attendances', 'grades'])->get();
        $academicYear = AcademicYear::where('is_current', true)->first();

        $childrenData = $children->map(function ($child) use ($academicYear) {
            $class = $child->enrolledClasses()->first();

            // Recent attendance
            $recentAttendance = $child->attendances()
                ->where('academic_year_id', $academicYear?->id)
                ->latest('date')
                ->limit(5)
                ->get();

            // Attendance stats
            $totalAttendance = $child->attendances()
                ->where('academic_year_id', $academicYear?->id)
                ->count();
            $presentCount = $child->attendances()
                ->where('academic_year_id', $academicYear?->id)
                ->whereIn('status', ['present', 'late'])
                ->count();

            // Recent grades
            $recentGrades = $child->grades()
                ->where('academic_year_id', $academicYear?->id)
                ->where('is_published', true)
                ->with('subject')
                ->latest()
                ->limit(3)
                ->get();

            // Grade average
            $gradeAverage = $child->grades()
                ->where('academic_year_id', $academicYear?->id)
                ->where('is_published', true)
                ->avg('total');

            return [
                'student' => $child,
                'class' => $class,
                'recent_attendance' => $recentAttendance,
                'attendance_rate' => $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0,
                'recent_grades' => $recentGrades,
                'grade_average' => $gradeAverage ? round($gradeAverage, 1) : null,
            ];
        });

        return view('parent.dashboard', compact('parent', 'childrenData', 'academicYear'));
    }

    /**
     * View specific child's details
     */
    public function childDetails(User $child): View
    {
        $parent = auth()->user();

        // Verify this is parent's child
        if (!$parent->children()->where('users.id', $child->id)->exists()) {
            abort(403);
        }

        $academicYear = AcademicYear::where('is_current', true)->first();
        $class = $child->enrolledClasses()->first();

        // Attendance stats
        $attendanceStats = [
            'total' => $child->attendances()->where('academic_year_id', $academicYear?->id)->count(),
            'present' => $child->attendances()->where('academic_year_id', $academicYear?->id)->where('status', 'present')->count(),
            'absent' => $child->attendances()->where('academic_year_id', $academicYear?->id)->where('status', 'absent')->count(),
            'late' => $child->attendances()->where('academic_year_id', $academicYear?->id)->where('status', 'late')->count(),
            'excused' => $child->attendances()->where('academic_year_id', $academicYear?->id)->where('status', 'excused')->count(),
        ];
        $attendanceStats['rate'] = $attendanceStats['total'] > 0
            ? round((($attendanceStats['present'] + $attendanceStats['late']) / $attendanceStats['total']) * 100, 1)
            : 0;

        // Grades by subject
        $grades = $child->grades()
            ->where('academic_year_id', $academicYear?->id)
            ->where('is_published', true)
            ->with('subject')
            ->get()
            ->groupBy('subject_id')
            ->map(function ($subjectGrades) {
                return [
                    'subject' => $subjectGrades->first()->subject,
                    'grades' => $subjectGrades,
                    'average' => round($subjectGrades->avg('total'), 1),
                ];
            });

        // Recent attendance
        $recentAttendance = $child->attendances()
            ->where('academic_year_id', $academicYear?->id)
            ->with(['subject', 'classRoom'])
            ->latest('date')
            ->limit(10)
            ->get();

        // Upcoming exams
        $upcomingExams = $class ? $class->exams()
            ->where('academic_year_id', $academicYear?->id)
            ->where('exam_date', '>=', now())
            ->whereIn('status', ['published', 'active'])
            ->with('subject')
            ->orderBy('exam_date')
            ->limit(5)
            ->get() : collect();

        return view('parent.child-details', compact(
            'parent',
            'child',
            'class',
            'academicYear',
            'attendanceStats',
            'grades',
            'recentAttendance',
            'upcomingExams'
        ));
    }

    /**
     * View child's grades
     */
    public function childGrades(User $child, Request $request): View
    {
        $parent = auth()->user();

        if (!$parent->children()->where('users.id', $child->id)->exists()) {
            abort(403);
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $selectedYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $grades = collect();

        if ($selectedYear) {
            $grades = $child->grades()
                ->where('academic_year_id', $selectedYear->id)
                ->where('is_published', true)
                ->with('subject')
                ->get()
                ->groupBy('subject_id')
                ->map(function ($subjectGrades) {
                    return [
                        'subject' => $subjectGrades->first()->subject,
                        'grades' => $subjectGrades,
                        'average' => round($subjectGrades->avg('total'), 1),
                    ];
                });
        }

        return view('parent.child-grades', compact('parent', 'child', 'academicYears', 'selectedYear', 'grades'));
    }

    /**
     * View child's attendance
     */
    public function childAttendance(User $child, Request $request): View
    {
        $parent = auth()->user();

        if (!$parent->children()->where('users.id', $child->id)->exists()) {
            abort(403);
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $selectedYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $attendances = collect();
        $stats = null;

        if ($selectedYear) {
            $attendances = $child->attendances()
                ->where('academic_year_id', $selectedYear->id)
                ->with(['subject', 'classRoom'])
                ->orderBy('date', 'desc')
                ->paginate(30);

            $allAttendances = $child->attendances()
                ->where('academic_year_id', $selectedYear->id)
                ->get();

            $total = $allAttendances->count();
            $present = $allAttendances->where('status', 'present')->count();
            $absent = $allAttendances->where('status', 'absent')->count();
            $late = $allAttendances->where('status', 'late')->count();
            $excused = $allAttendances->where('status', 'excused')->count();

            $stats = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'attendance_rate' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0,
            ];
        }

        return view('parent.child-attendance', compact('parent', 'child', 'academicYears', 'selectedYear', 'attendances', 'stats'));
    }

    /**
     * View child's schedule
     */
    public function childSchedule(User $child): View
    {
        $parent = auth()->user();

        if (!$parent->children()->where('users.id', $child->id)->exists()) {
            abort(403);
        }

        $class = $child->enrolledClasses()->first();
        $academicYear = AcademicYear::where('is_current', true)->first();

        $schedule = null;
        $periods = collect();

        if ($class && $academicYear) {
            $schedule = $class->schedules()
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->first();

            if ($schedule) {
                $periods = $schedule->periods()
                    ->with(['subject', 'teacher'])
                    ->orderBy('day_of_week')
                    ->orderBy('period_number')
                    ->get()
                    ->groupBy('day_of_week');
            }
        }

        $days = ['sunday' => 'الأحد', 'monday' => 'الإثنين', 'tuesday' => 'الثلاثاء', 'wednesday' => 'الأربعاء', 'thursday' => 'الخميس'];

        return view('parent.child-schedule', compact('parent', 'child', 'class', 'schedule', 'periods', 'days'));
    }

    /**
     * Contact teacher - list all teachers for children
     */
    public function contactTeacher(): View
    {
        $parent = auth()->user();
        $children = $parent->children()->with(['classEnrollments.classRoom'])->get();

        foreach ($children as $child) {
            $childClass = $child->classEnrollments()
                ->whereHas('academicYear', fn($q) => $q->where('is_current', true))
                ->first()?->classRoom;

            if ($childClass) {
                $teachers = User::whereHas('scheduleTeacher.schedule', function ($query) use ($childClass) {
                    $query->where('class_id', $childClass->id);
                })
                    ->with(['scheduleTeacher.subject'])
                    ->get()
                    ->map(function ($teacher) {
                        $subject = $teacher->scheduleTeacher->first()?->subject;
                        $teacher->pivot = (object) ['subject_name' => $subject?->name];
                        return $teacher;
                    });

                $child->teachers = $teachers;
            } else {
                $child->teachers = collect();
            }
        }

        return view('parent.contact-teacher', compact('parent', 'children'));
    }
}
