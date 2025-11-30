<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * عرض صفحة التقارير الرئيسية
     */
    public function index(): View
    {
        $user = auth()->user();
        $academicYears = AcademicYear::orderByDesc('is_current')->orderByDesc('start_date')->get();
        $currentYear = AcademicYear::where('is_current', true)->first();

        if ($user->isSuperAdmin()) {
            $classes = ClassRoom::with('section')->orderBy('name')->get();
        } else {
            $classes = ClassRoom::whereHas('section', fn($q) => $q->where('school_id', $user->school_id))
                ->with('section')
                ->orderBy('name')
                ->get();
        }

        $subjects = Subject::when(!$user->isSuperAdmin(), fn($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')
            ->get();

        return view('admin.reports.index', compact('academicYears', 'currentYear', 'classes', 'subjects'));
    }

    /**
     * تقرير بطاقة الطالب
     */
    public function studentCard(Request $request): View
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $student = User::findOrFail($request->student_id);
        $academicYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $enrollment = $student->classEnrollments()
            ->where('academic_year_id', $academicYear?->id)
            ->with('classRoom.section')
            ->first();

        $grades = Grade::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear?->id)
            ->with('subject')
            ->get()
            ->groupBy('subject_id')
            ->map(function ($subjectGrades) {
                $subject = $subjectGrades->first()->subject;
                $terms = $subjectGrades->keyBy('term');
                $average = $subjectGrades->avg('total');

                return [
                    'subject' => $subject,
                    'terms' => $terms,
                    'average' => round($average, 1),
                ];
            });

        $attendanceStats = $this->getAttendanceStats($student->id, $academicYear?->id);

        $academicYears = AcademicYear::orderByDesc('is_current')->orderByDesc('start_date')->get();

        return view('admin.reports.student-card', compact(
            'student',
            'academicYear',
            'enrollment',
            'grades',
            'attendanceStats',
            'academicYears'
        ));
    }

    /**
     * تصدير بطاقة الطالب PDF
     */
    public function studentCardPdf(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $student = User::findOrFail($request->student_id);
        $academicYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $enrollment = $student->classEnrollments()
            ->where('academic_year_id', $academicYear?->id)
            ->with('classRoom.section')
            ->first();

        $grades = Grade::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear?->id)
            ->with('subject')
            ->get()
            ->groupBy('subject_id')
            ->map(function ($subjectGrades) {
                $subject = $subjectGrades->first()->subject;
                $terms = $subjectGrades->keyBy('term');
                $average = $subjectGrades->avg('total');

                return [
                    'subject' => $subject,
                    'terms' => $terms,
                    'average' => round($average, 1),
                ];
            });

        $attendanceStats = $this->getAttendanceStats($student->id, $academicYear?->id);

        $pdf = Pdf::loadView('admin.reports.pdf.student-card', compact(
            'student',
            'academicYear',
            'enrollment',
            'grades',
            'attendanceStats'
        ));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("student-card-{$student->id}.pdf");
    }

    /**
     * تقرير الصف
     */
    public function classReport(Request $request): View
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $class = ClassRoom::with('section')->findOrFail($request->class_id);
        $academicYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $students = User::whereHas('classEnrollments', function ($query) use ($class, $academicYear) {
            $query->where('class_id', $class->id)
                ->where('academic_year_id', $academicYear?->id);
        })->get();

        $subjectId = $request->subject_id;
        $subject = $subjectId ? Subject::find($subjectId) : null;

        $studentsData = $students->map(function ($student) use ($academicYear, $subjectId) {
            $gradesQuery = Grade::where('student_id', $student->id)
                ->where('academic_year_id', $academicYear?->id);

            if ($subjectId) {
                $gradesQuery->where('subject_id', $subjectId);
            }

            $grades = $gradesQuery->get();
            $average = $grades->avg('total');

            $attendanceStats = $this->getAttendanceStats($student->id, $academicYear?->id);

            return [
                'student' => $student,
                'average' => round($average ?? 0, 1),
                'grades_count' => $grades->count(),
                'attendance_rate' => $attendanceStats['rate'],
            ];
        })->sortByDesc('average')->values();

        $classAverage = $studentsData->avg('average');
        $classAttendanceRate = $studentsData->avg('attendance_rate');

        $academicYears = AcademicYear::orderByDesc('is_current')->orderByDesc('start_date')->get();
        $subjects = Subject::when(!auth()->user()->isSuperAdmin(), fn($q) => $q->where('school_id', auth()->user()->school_id))
            ->orderBy('name')
            ->get();

        return view('admin.reports.class-report', compact(
            'class',
            'academicYear',
            'subject',
            'studentsData',
            'classAverage',
            'classAttendanceRate',
            'academicYears',
            'subjects'
        ));
    }

    /**
     * تصدير تقرير الصف PDF
     */
    public function classReportPdf(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $class = ClassRoom::with('section')->findOrFail($request->class_id);
        $academicYear = $request->academic_year_id
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::where('is_current', true)->first();

        $students = User::whereHas('classEnrollments', function ($query) use ($class, $academicYear) {
            $query->where('class_id', $class->id)
                ->where('academic_year_id', $academicYear?->id);
        })->get();

        $subjectId = $request->subject_id;
        $subject = $subjectId ? Subject::find($subjectId) : null;

        $studentsData = $students->map(function ($student) use ($academicYear, $subjectId) {
            $gradesQuery = Grade::where('student_id', $student->id)
                ->where('academic_year_id', $academicYear?->id);

            if ($subjectId) {
                $gradesQuery->where('subject_id', $subjectId);
            }

            $grades = $gradesQuery->get();
            $average = $grades->avg('total');

            $attendanceStats = $this->getAttendanceStats($student->id, $academicYear?->id);

            return [
                'student' => $student,
                'average' => round($average ?? 0, 1),
                'grades_count' => $grades->count(),
                'attendance_rate' => $attendanceStats['rate'],
            ];
        })->sortByDesc('average')->values();

        $classAverage = $studentsData->avg('average');
        $classAttendanceRate = $studentsData->avg('attendance_rate');

        $pdf = Pdf::loadView('admin.reports.pdf.class-report', compact(
            'class',
            'academicYear',
            'subject',
            'studentsData',
            'classAverage',
            'classAttendanceRate'
        ));

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("class-report-{$class->id}.pdf");
    }

    /**
     * تقرير الحضور الشهري
     */
    public function attendanceReport(Request $request): View
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $class = ClassRoom::with('section')->findOrFail($request->class_id);
        $month = $request->month ?? now()->format('Y-m');
        $academicYear = AcademicYear::where('is_current', true)->first();

        $students = User::whereHas('classEnrollments', function ($query) use ($class, $academicYear) {
            $query->where('class_id', $class->id)
                ->where('academic_year_id', $academicYear?->id);
        })->get();

        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $attendanceData = $students->map(function ($student) use ($startDate, $endDate) {
            $attendance = Attendance::where('student_id', $student->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            return [
                'student' => $student,
                'present' => $attendance->where('status', 'present')->count(),
                'absent' => $attendance->where('status', 'absent')->count(),
                'late' => $attendance->where('status', 'late')->count(),
                'excused' => $attendance->where('status', 'excused')->count(),
                'total' => $attendance->count(),
                'rate' => $attendance->count() > 0
                    ? round(($attendance->whereIn('status', ['present', 'late'])->count() / $attendance->count()) * 100, 1)
                    : 0,
            ];
        });

        $user = auth()->user();
        $classes = ClassRoom::when(!$user->isSuperAdmin(), function ($q) use ($user) {
            $q->whereHas('section', fn($sq) => $sq->where('school_id', $user->school_id));
        })->with('section')->orderBy('name')->get();

        return view('admin.reports.attendance-report', compact(
            'class',
            'month',
            'attendanceData',
            'classes'
        ));
    }

    /**
     * تصدير تقرير الحضور PDF
     */
    public function attendanceReportPdf(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $class = ClassRoom::with('section')->findOrFail($request->class_id);
        $month = $request->month ?? now()->format('Y-m');
        $academicYear = AcademicYear::where('is_current', true)->first();

        $students = User::whereHas('classEnrollments', function ($query) use ($class, $academicYear) {
            $query->where('class_id', $class->id)
                ->where('academic_year_id', $academicYear?->id);
        })->get();

        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $attendanceData = $students->map(function ($student) use ($startDate, $endDate) {
            $attendance = Attendance::where('student_id', $student->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            return [
                'student' => $student,
                'present' => $attendance->where('status', 'present')->count(),
                'absent' => $attendance->where('status', 'absent')->count(),
                'late' => $attendance->where('status', 'late')->count(),
                'excused' => $attendance->where('status', 'excused')->count(),
                'total' => $attendance->count(),
                'rate' => $attendance->count() > 0
                    ? round(($attendance->whereIn('status', ['present', 'late'])->count() / $attendance->count()) * 100, 1)
                    : 0,
            ];
        });

        $pdf = Pdf::loadView('admin.reports.pdf.attendance-report', compact(
            'class',
            'month',
            'attendanceData'
        ));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("attendance-report-{$class->id}-{$month}.pdf");
    }

    /**
     * إحصائيات الحضور للطالب
     */
    private function getAttendanceStats(int $studentId, ?int $academicYearId): array
    {
        $query = Attendance::where('student_id', $studentId);

        if ($academicYearId) {
            $academicYear = AcademicYear::find($academicYearId);
            if ($academicYear) {
                $query->whereBetween('date', [$academicYear->start_date, $academicYear->end_date]);
            }
        }

        $attendance = $query->get();

        $total = $attendance->count();
        $present = $attendance->where('status', 'present')->count();
        $absent = $attendance->where('status', 'absent')->count();
        $late = $attendance->where('status', 'late')->count();
        $excused = $attendance->where('status', 'excused')->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'rate' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0,
        ];
    }

    /**
     * لوحة الإحصائيات
     */
    public function analytics(): View
    {
        $user = auth()->user();
        $academicYear = AcademicYear::where('is_current', true)->first();

        $classesQuery = ClassRoom::query();
        if (!$user->isSuperAdmin()) {
            $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $user->school_id));
        }

        $classes = $classesQuery->with('section')->get();

        $classStats = $classes->map(function ($class) use ($academicYear) {
            $studentIds = DB::table('class_enrollments')
                ->where('class_id', $class->id)
                ->where('academic_year_id', $academicYear?->id)
                ->pluck('student_id');

            $gradesAvg = Grade::whereIn('student_id', $studentIds)
                ->where('academic_year_id', $academicYear?->id)
                ->avg('total');

            $attendanceData = Attendance::whereIn('student_id', $studentIds)->get();
            $attendanceRate = $attendanceData->count() > 0
                ? round(($attendanceData->whereIn('status', ['present', 'late'])->count() / $attendanceData->count()) * 100, 1)
                : 0;

            return [
                'class' => $class,
                'students_count' => $studentIds->count(),
                'grades_average' => round($gradesAvg ?? 0, 1),
                'attendance_rate' => $attendanceRate,
            ];
        });

        $overallStats = [
            'total_students' => $classStats->sum('students_count'),
            'average_grades' => round($classStats->avg('grades_average'), 1),
            'average_attendance' => round($classStats->avg('attendance_rate'), 1),
        ];

        $topClasses = $classStats->sortByDesc('grades_average')->take(5);
        $bottomClasses = $classStats->sortBy('grades_average')->take(5);

        return view('admin.reports.analytics', compact(
            'classStats',
            'overallStats',
            'topClasses',
            'bottomClasses',
            'academicYear'
        ));
    }
}
