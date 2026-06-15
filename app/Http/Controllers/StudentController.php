<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStudentScope;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\StudentExam;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    use ResolvesStudentScope;

    /**
     * Student dashboard
     */
    public function dashboard(): View
    {
        $student = auth()->user();
        $academicYear = $this->effectiveAcademicYear($student);

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
        // Default to the scope's effective year (re-scoped by the navbar year
        // selector); an explicit ?academic_year_id on the page filter still
        // overrides when the student is permitted to view previous periods.
        $selectedYear = ($request->academic_year_id && $this->studentMayViewPreviousPeriods($student))
            ? AcademicYear::where('school_id', $student->school_id)->find($request->academic_year_id)
            : $this->effectiveAcademicYear($student);

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
        // Default to the scope's effective year (re-scoped by the navbar year
        // selector); an explicit ?academic_year_id on the page filter still
        // overrides when the student is permitted to view previous periods.
        $selectedYear = ($request->academic_year_id && $this->studentMayViewPreviousPeriods($student))
            ? AcademicYear::where('school_id', $student->school_id)->find($request->academic_year_id)
            : $this->effectiveAcademicYear($student);

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
        // Default to the scope's effective year (re-scoped by the navbar year
        // selector); an explicit ?academic_year_id on the page filter still
        // overrides when the student is permitted to view previous periods.
        $selectedYear = ($request->academic_year_id && $this->studentMayViewPreviousPeriods($student))
            ? AcademicYear::where('school_id', $student->school_id)->find($request->academic_year_id)
            : $this->effectiveAcademicYear($student);

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
        $academicYear = $this->effectiveAcademicYear($student);

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

    // =========================================================
    // Card #172 — absence reports, portfolio, exam schedule
    // =========================================================

    /**
     * Reports index — links to the three absence sub-reports.
     */
    public function reportsIndex(): View
    {
        return view('student.reports.index');
    }

    /**
     * Absence days — full-day absences (absent + excused) with date filter.
     */
    public function absenceDays(Request $request): View
    {
        $student   = auth()->user();
        $academicYear = AcademicYear::where('is_current', true)->first();

        $query = $student->attendances()
            ->with(['subject', 'classRoom'])
            ->whereIn('status', ['absent', 'excused'])
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->from))
            ->when($request->filled('to'),   fn ($q) => $q->whereDate('date', '<=', $request->to))
            ->orderByDesc('date');

        $absences = $query->get();

        return view('student.reports.absence-days', compact('absences', 'academicYear'));
    }

    /**
     * Absence summary — present / absent / late / excused counts + rate.
     */
    public function absenceSummary(): View
    {
        $student      = auth()->user();
        $academicYear = AcademicYear::where('is_current', true)->first();

        $all = $student->attendances()
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->get();

        $total   = $all->count();
        $present = $all->where('status', 'present')->count();
        $absent  = $all->where('status', 'absent')->count();
        $late    = $all->where('status', 'late')->count();
        $excused = $all->where('status', 'excused')->count();

        $attendanceRate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0;

        $stats = compact('total', 'present', 'absent', 'late', 'excused', 'attendanceRate');

        return view('student.reports.absence-summary', compact('stats', 'academicYear'));
    }

    /**
     * Absence by subject — absences grouped by subject_id (period attendance).
     * Falls back gracefully if no subject is linked (groups as "بدون مادة").
     */
    public function absenceBySubject(): View
    {
        $student      = auth()->user();
        $academicYear = AcademicYear::where('is_current', true)->first();

        $absences = $student->attendances()
            ->with('subject')
            ->whereIn('status', ['absent', 'excused'])
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->orderByDesc('date')
            ->get();

        // Group by subject name; rows without a linked subject go to "بدون مادة"
        $grouped = $absences->groupBy(fn ($a) => $a->subject?->name ?? 'بدون مادة');

        return view('student.reports.absence-by-subject', compact('grouped', 'academicYear'));
    }

    /**
     * Exam schedule — all published exams for the student's class(es),
     * sorted chronologically, with the student's own result attached.
     */
    public function examSchedule(): View
    {
        $student      = auth()->user();
        $academicYear = AcademicYear::where('is_current', true)->first();
        $classIds     = $student->enrolledClassIds();

        $exams = collect();
        $resultsByExam = collect();

        if (! empty($classIds)) {
            $exams = Exam::whereIn('class_id', $classIds)
                ->where('is_published', true)
                ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
                ->with('subject')
                ->orderBy('start_time')
                ->get();

            // Student's submitted attempts keyed by exam_id
            $resultsByExam = $student->studentExams()
                ->whereNotNull('submitted_at')
                ->whereIn('exam_id', $exams->pluck('id'))
                ->get()
                ->keyBy('exam_id');
        }

        return view('student.reports.exam-schedule', compact('exams', 'resultsByExam', 'academicYear'));
    }

    /**
     * Portfolio — aggregate of certificates, grades, exam results, attendance.
     */
    /**
     * The student's own special-education record (read-only), if any. Scoped to
     * the logged-in student — never another student's record (#173).
     */
    public function specialEducation(): View
    {
        $student = auth()->user();

        $record = \App\Models\SpecialEducationStudent::with(['specialist:id,name', 'plans', 'notes'])
            ->where('student_id', $student->id)
            ->when($student->school_id, fn ($q) => $q->where('school_id', $student->school_id))
            ->first();

        return view('student.special-education', compact('record'));
    }

    public function portfolio(): View
    {
        $student      = auth()->user();
        $academicYear = AcademicYear::where('is_current', true)->first();

        // Published certificates issued to this student
        $certificates = Certificate::where('recipient_user_id', $student->id)
            ->where('status', 'published')
            ->when($student->school_id, fn ($q) => $q->where('school_id', $student->school_id))
            ->orderByDesc('issue_date')
            ->get();

        // Published grades with subject
        $grades = $student->grades()
            ->where('is_published', true)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->with('subject')
            ->get();

        $avgGrade  = $grades->count() > 0 ? round($grades->avg('total'), 1) : null;
        $topGrades = $grades->sortByDesc('total')->take(5);

        // Recent submitted exam results
        $examResults = $student->studentExams()
            ->whereNotNull('submitted_at')
            ->with(['exam.subject'])
            ->latest('submitted_at')
            ->take(10)
            ->get();

        // Attendance rate for current year
        $allAttendances = $student->attendances()
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->get();
        $attTotal        = $allAttendances->count();
        $attPresent      = $allAttendances->where('status', 'present')->count();
        $attLate         = $allAttendances->where('status', 'late')->count();
        $attendanceRate  = $attTotal > 0 ? round((($attPresent + $attLate) / $attTotal) * 100, 1) : 0;

        return view('student.portfolio', compact(
            'student', 'academicYear', 'certificates',
            'grades', 'avgGrade', 'topGrades',
            'examResults', 'attendanceRate'
        ));
    }

    /**
     * View weekly plans
     */
    public function weeklyPlans(Request $request): View
    {
        $student = auth()->user();
        $class = $student->enrolledClasses()->first();
        $academicYear = $this->effectiveAcademicYear($student);
        $academicTerm = $this->effectiveAcademicTerm($student, $academicYear);

        $weeklyPlans = collect();

        if ($class) {
            // weekly_plans has no academic_year_id/term_id column — it is scoped
            // by the week date range. Honour the effective term's window when one
            // is resolved (so term switching re-scopes plans), else the year's.
            [$periodStart, $periodEnd] = $this->periodWindow($academicTerm, $academicYear);

            $query = $class->weeklyPlans()
                ->where('is_locked', true)
                ->when($periodStart, fn ($q) => $q->whereDate('week_start_date', '>=', $periodStart))
                ->when($periodEnd, fn ($q) => $q->whereDate('week_start_date', '<=', $periodEnd))
                ->with(['subject', 'teacher'])
                ->orderBy('week_start_date', 'desc');

            if ($request->subject_id) {
                $query->where('subject_id', $request->subject_id);
            }

            $weeklyPlans = $query->paginate(10);
        }

        // Subjects for the filter come from the student's grade-resolved subjects
        // (ClassRoom has no subjects() relation), school-scoped.
        $subjects = $this->studentSubjectsForFilter($student);

        return view('student.weekly-plans', compact('student', 'class', 'weeklyPlans', 'subjects'));
    }

    /**
     * Resolve the [start, end] date window for the effective period. Prefers the
     * term window (when both dates are set), falling back to the academic year.
     *
     * @return array{0:?string,1:?string}
     */
    private function periodWindow(?\App\Models\AcademicTerm $term, ?AcademicYear $year): array
    {
        if ($term && $term->start_date && $term->end_date) {
            return [$term->start_date->toDateString(), $term->end_date->toDateString()];
        }
        if ($year && $year->start_date && $year->end_date) {
            return [$year->start_date->toDateString(), $year->end_date->toDateString()];
        }

        return [null, null];
    }

    /**
     * Subjects available to the student (by grade level), school-scoped — used
     * for page filters. ClassRoom has no subjects() relation, so resolve the same
     * way StudentSubjectController does.
     */
    private function studentSubjectsForFilter($student)
    {
        $gradeLevel = optional($student->classRoom)->grade_level;

        return \App\Models\Subject::where('school_id', $student->school_id)
            ->where('is_active', true)
            ->when($gradeLevel !== null, function ($q) use ($gradeLevel) {
                $q->where(function ($w) use ($gradeLevel) {
                    $w->whereJsonContains('grade_levels', (string) $gradeLevel)
                        ->orWhereJsonContains('grade_levels', (int) $gradeLevel);
                });
            })
            ->orderBy('name')
            ->get();
    }
}
