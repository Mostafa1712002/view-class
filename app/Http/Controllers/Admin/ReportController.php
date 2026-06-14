<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
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

        // Resolve the student's class from the real sources: the class_room_id
        // column or the class_student pivot (enrolledClasses). Shaped as an
        // object exposing ->classRoom so the shared views keep working.
        $classRoom = $student->classRoom ?: $student->enrolledClasses()->first();
        $classRoom?->loadMissing('section');
        $enrollment = $classRoom ? (object) ['classRoom' => $classRoom] : null;

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

        // Resolve the student's class from the real sources: the class_room_id
        // column or the class_student pivot (enrolledClasses). Shaped as an
        // object exposing ->classRoom so the shared views keep working.
        $classRoom = $student->classRoom ?: $student->enrolledClasses()->first();
        $classRoom?->loadMissing('section');
        $enrollment = $classRoom ? (object) ['classRoom' => $classRoom] : null;

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

        $html = view('admin.reports.pdf.student-card', compact(
            'student', 'academicYear', 'enrollment', 'grades', 'attendanceStats'
        ))->render();

        $filename = "student-card-{$student->id}.pdf";

        return $this->mpdfResponse($html, $filename, 'P');
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

        $students = User::where(function ($query) use ($class) {
            $query->where('class_room_id', $class->id)
                ->orWhereHas('enrolledClasses', fn ($q) => $q->where('classes.id', $class->id));
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

        $students = User::where(function ($query) use ($class) {
            $query->where('class_room_id', $class->id)
                ->orWhereHas('enrolledClasses', fn ($q) => $q->where('classes.id', $class->id));
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

        $html = view('admin.reports.pdf.class-report', compact(
            'class', 'academicYear', 'subject', 'studentsData', 'classAverage', 'classAttendanceRate'
        ))->render();

        $filename = "class-report-{$class->id}.pdf";

        return $this->mpdfResponse($html, $filename, 'L');
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

        $students = User::where(function ($query) use ($class) {
            $query->where('class_room_id', $class->id)
                ->orWhereHas('enrolledClasses', fn ($q) => $q->where('classes.id', $class->id));
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

        $students = User::where(function ($query) use ($class) {
            $query->where('class_room_id', $class->id)
                ->orWhereHas('enrolledClasses', fn ($q) => $q->where('classes.id', $class->id));
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

        $html = view('admin.reports.pdf.attendance-report', compact('class', 'month', 'attendanceData'))->render();

        $filename = "attendance-report-{$class->id}-{$month}.pdf";

        return $this->mpdfResponse($html, $filename, 'P');
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
            $studentIds = DB::table('class_student')
                ->where('class_id', $class->id)
                ->pluck('student_id')
                ->merge(User::where('class_room_id', $class->id)->pluck('id'))
                ->unique()
                ->values();

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

    // ============================================================
    // Sprint 5 — Reports module: Administrative / Statistical / User
    // ============================================================

    public function administrative(Request $request): View
    {
        return view('admin.reports.administrative');
    }

    public function statistical(Request $request): View
    {
        return view('admin.reports.statistical');
    }

    public function userReports(Request $request): View
    {
        $tab = $request->get('tab', 'teachers');
        $user = auth()->user();
        $schoolId = $user->isSuperAdmin() ? null : $user->school_id;

        $rows = collect();
        $perPage = 20;
        if ($tab === 'teachers') {
            $rows = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'))
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->paginate($perPage);
        } elseif ($tab === 'students') {
            $rows = User::whereHas('roles', fn($q) => $q->where('slug', 'student'))
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->paginate($perPage);
        } elseif ($tab === 'parents') {
            $rows = User::whereHas('roles', fn($q) => $q->where('slug', 'parent'))
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->paginate($perPage);
        }

        return view('admin.reports.user-reports', compact('tab', 'rows'));
    }

    /**
     * تقرير المدارس العام — counts per school within the user's scope.
     */
    public function schoolsGeneral(Request $request): View
    {
        $user = auth()->user();
        $schoolsQuery = \App\Models\School::query()
            ->when(!$user->isSuperAdmin(), fn($q) => $q->where('id', $user->school_id));

        $schools = $schoolsQuery->get();
        $rows = $schools->map(function ($s) {
            $studentsCount = User::whereHas('roles', fn($q) => $q->where('slug', 'student'))
                ->where('school_id', $s->id)->count();
            $teachersCount = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'))
                ->where('school_id', $s->id)->count();
            $classesCount = ClassRoom::whereHas('section', fn($q) => $q->where('school_id', $s->id))->count();
            return (object) [
                'school' => $s,
                'students' => $studentsCount,
                'teachers' => $teachersCount,
                'classes' => $classesCount,
            ];
        });

        return view('admin.reports.schools-general', compact('rows'));
    }

    /**
     * Shared mPDF helper — renders pre-built HTML to PDF and returns a response.
     * Uses xbriyaz font for proper Arabic glyph shaping + RTL bidi.
     */
    private function mpdfResponse(string $html, string $filename, string $orientation = 'P'): \Illuminate\Http\Response
    {
        $tmp = storage_path('app/mpdf');
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'orientation'      => $orientation,
            'default_font'     => 'xbriyaz',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'tempDir'          => $tmp,
            'margin_top'       => 15,
            'margin_bottom'    => 15,
            'margin_left'      => 12,
            'margin_right'     => 12,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:8px;color:#94a3b8;font-family:dejavusans;">'
            . 'صفحة {PAGENO} من {nb}'
            . '</div>'
        );
        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }
}
