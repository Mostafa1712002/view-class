<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\StudentExam;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Student dashboard
     */
    public function dashboard(): View
    {
        $student = auth()->user();
        $academicYear = AcademicYear::where('is_current', true)->first();

        // Get student's class
        $class = $student->enrolledClasses()->first();

        // Recent attendance
        $recentAttendance = $student->attendances()
            ->where('academic_year_id', $academicYear?->id)
            ->latest('date')
            ->limit(7)
            ->get();

        // Attendance stats
        $attendanceStats = [
            'present' => $student->attendances()->where('academic_year_id', $academicYear?->id)->where('status', 'present')->count(),
            'absent' => $student->attendances()->where('academic_year_id', $academicYear?->id)->where('status', 'absent')->count(),
            'late' => $student->attendances()->where('academic_year_id', $academicYear?->id)->where('status', 'late')->count(),
        ];

        // Recent grades
        $recentGrades = $student->grades()
            ->where('academic_year_id', $academicYear?->id)
            ->where('is_published', true)
            ->with('subject')
            ->latest()
            ->limit(5)
            ->get();

        // Grade average
        $gradeAverage = $student->grades()
            ->where('academic_year_id', $academicYear?->id)
            ->where('is_published', true)
            ->avg('total');

        // Upcoming / available exams for the student's class(es).
        $classIds = $student->enrolledClassIds();
        $upcomingExams = collect();
        if (! empty($classIds)) {
            $now = now();
            $upcomingExams = Exam::whereIn('class_id', $classIds)
                ->where('is_published', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_time')
                        ->orWhere('end_time', '>=', $now);
                })
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('start_time')
                ->limit(5)
                ->get();
        }

        // Recent exam results — submitted attempts only
        $recentExamResults = $student->studentExams()
            ->whereNotNull('submitted_at')
            ->with(['exam.subject'])
            ->latest()
            ->limit(5)
            ->get();

        return view('student.dashboard', compact(
            'student',
            'class',
            'academicYear',
            'recentAttendance',
            'attendanceStats',
            'recentGrades',
            'gradeAverage',
            'upcomingExams',
            'recentExamResults'
        ));
    }

    /**
     * View student's grades
     */
    public function grades(Request $request): View
    {
        $student = auth()->user();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $selectedYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $grades = collect();
        $subjects = collect();

        if ($selectedYear) {
            $grades = $student->grades()
                ->where('academic_year_id', $selectedYear->id)
                ->where('is_published', true)
                ->with('subject')
                ->get()
                ->groupBy('subject_id');

            $subjects = $grades->keys()->map(function ($subjectId) use ($grades) {
                $subjectGrades = $grades[$subjectId];
                $subject = $subjectGrades->first()->subject;
                return [
                    'subject' => $subject,
                    'grades' => $subjectGrades,
                    'average' => $subjectGrades->avg('total'),
                ];
            });
        }

        return view('student.grades', compact('student', 'academicYears', 'selectedYear', 'subjects'));
    }

    /**
     * View student's attendance
     */
    public function attendance(Request $request): View
    {
        $student = auth()->user();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $selectedYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $attendances = collect();
        $stats = null;
        $monthlyStats = collect();

        if ($selectedYear) {
            $attendances = $student->attendances()
                ->where('academic_year_id', $selectedYear->id)
                ->with(['subject', 'classRoom'])
                ->orderBy('date', 'desc')
                ->paginate(30);

            // Calculate stats
            $allAttendances = $student->attendances()
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

            // Monthly stats
            $monthlyStats = $allAttendances->groupBy(function ($item) {
                return $item->date->format('Y-m');
            })->map(function ($items) {
                return [
                    'present' => $items->where('status', 'present')->count(),
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count(),
                    'excused' => $items->where('status', 'excused')->count(),
                ];
            });
        }

        return view('student.attendance', compact('student', 'academicYears', 'selectedYear', 'attendances', 'stats', 'monthlyStats'));
    }

    /**
     * View student's exams
     */
    public function exams(Request $request): View
    {
        $student = auth()->user();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $selectedYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $upcomingExams = collect();
        $completedExams = collect();

        // Class IDs already submitted by this student keyed by exam id, so the
        // view can tell which available exams still need to be taken.
        $submittedExamIds = collect();

        if ($selectedYear) {
            // Resolve the student's class from both enrollment sources
            // (class_student pivot + users.class_room_id).
            $classIds = $student->enrolledClassIds();

            if (! empty($classIds)) {
                $now = now();

                // Available exams: published, not yet ended, in the student's
                // class. Includes exams already in progress (start_time in the
                // past) as long as the window has not closed — those are the
                // ones the student should be able to enter and take right now.
                $upcomingExams = Exam::whereIn('class_id', $classIds)
                    ->where('academic_year_id', $selectedYear->id)
                    ->where('is_published', true)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_time')
                            ->orWhere('end_time', '>=', $now);
                    })
                    ->whereNotIn('status', ['cancelled'])
                    ->with('subject')
                    ->orderBy('start_time')
                    ->get();

                // IDs the student already submitted (hide their "enter" action).
                $submittedExamIds = $student->studentExams()
                    ->whereNotNull('submitted_at')
                    ->pluck('exam_id');

                // Completed exams with results — submitted attempts.
                $completedExams = $student->studentExams()
                    ->whereHas('exam', function ($q) use ($selectedYear, $classIds) {
                        $q->where('academic_year_id', $selectedYear->id)
                            ->whereIn('class_id', $classIds);
                    })
                    ->whereNotNull('submitted_at')
                    ->with(['exam.subject'])
                    ->get();
            }
        }

        return view('student.exams', compact('student', 'academicYears', 'selectedYear', 'upcomingExams', 'completedExams', 'submittedExamIds'));
    }

    /**
     * View student's schedule
     */
    public function schedule(): View
    {
        $student = auth()->user();
        $class = $student->enrolledClasses()->first();
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

        return view('student.schedule', compact('student', 'class', 'schedule', 'periods', 'days'));
    }

    /**
     * View weekly plans
     */
    public function weeklyPlans(Request $request): View
    {
        $student = auth()->user();
        $class = $student->enrolledClasses()->first();
        $academicYear = AcademicYear::where('is_current', true)->first();

        $weeklyPlans = collect();

        if ($class && $academicYear) {
            $query = $class->weeklyPlans()
                ->where('academic_year_id', $academicYear->id)
                ->where('is_locked', true)
                ->with(['subject', 'teacher'])
                ->orderBy('week_start_date', 'desc');

            if ($request->subject_id) {
                $query->where('subject_id', $request->subject_id);
            }

            $weeklyPlans = $query->paginate(10);
        }

        $subjects = $class ? $class->subjects : collect();

        return view('student.weekly-plans', compact('student', 'class', 'weeklyPlans', 'subjects'));
    }
}
