<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display attendance entry interface.
     */
    public function index(Request $request)
    {
        $classes = ClassRoom::with('students')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $attendances = collect();
        $selectedClass = null;
        $selectedDate = $request->date ?? now()->format('Y-m-d');
        $selectedPeriod = $request->period;

        if ($request->filled('class_id')) {
            $selectedClass = ClassRoom::with('students')->find($request->class_id);

            if ($selectedClass) {
                // Get existing attendance records or create new ones
                foreach ($selectedClass->students as $student) {
                    $attendance = Attendance::firstOrNew([
                        'student_id' => $student->id,
                        'class_id' => $request->class_id,
                        'date' => $selectedDate,
                        'period' => $selectedPeriod,
                    ], [
                        'teacher_id' => Auth::id(),
                        'academic_year_id' => $request->academic_year_id ?? AcademicYear::where('is_current', true)->first()?->id,
                        'subject_id' => $request->subject_id,
                        'status' => 'present',
                    ]);

                    $attendance->student = $student;
                    $attendances->push($attendance);
                }
            }
        }

        return view('admin.attendance.index', compact(
            'classes',
            'subjects',
            'academicYears',
            'attendances',
            'selectedClass',
            'selectedDate',
            'selectedPeriod'
        ));
    }

    /**
     * Store attendance records for multiple students.
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'academic_year_id' => 'required|exists:academic_years,id',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.arrival_time' => 'nullable|date_format:H:i',
            'attendances.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->attendances as $data) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $data['student_id'],
                        'class_id' => $request->class_id,
                        'date' => $request->date,
                        'period' => $request->period,
                    ],
                    [
                        'teacher_id' => Auth::id(),
                        'academic_year_id' => $request->academic_year_id,
                        'subject_id' => $request->subject_id,
                        'status' => $data['status'],
                        'arrival_time' => $data['status'] === 'late' ? ($data['arrival_time'] ?? null) : null,
                        'notes' => $data['notes'] ?? null,
                    ]
                );
            }
        });

        return back()->with('success', 'تم حفظ الحضور بنجاح.');
    }

    /**
     * Mark all students as present.
     */
    public function markAllPresent(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $classRoom = ClassRoom::with('students')->find($request->class_id);

        DB::transaction(function () use ($request, $classRoom) {
            foreach ($classRoom->students as $student) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'class_id' => $request->class_id,
                        'date' => $request->date,
                        'period' => $request->period,
                    ],
                    [
                        'teacher_id' => Auth::id(),
                        'academic_year_id' => $request->academic_year_id,
                        'subject_id' => $request->subject_id,
                        'status' => 'present',
                    ]
                );
            }
        });

        return back()->with('success', 'تم تسجيل حضور جميع الطلاب.');
    }

    /**
     * Display daily attendance report.
     */
    public function dailyReport(Request $request)
    {
        $classes = ClassRoom::orderBy('name')->get();
        $date = $request->date ?? now()->format('Y-m-d');

        $report = null;

        if ($request->filled('class_id')) {
            $classRoom = ClassRoom::with('students')->find($request->class_id);

            $attendances = Attendance::with('student')
                ->where('class_id', $request->class_id)
                ->whereDate('date', $date)
                ->get()
                ->groupBy('student_id');

            $report = [
                'class' => $classRoom,
                'date' => $date,
                'attendances' => $attendances,
                'stats' => Attendance::getClassStats($request->class_id, $date),
            ];
        }

        return view('admin.attendance.daily-report', compact('classes', 'date', 'report'));
    }

    /**
     * Display student attendance report.
     */
    public function studentReport(Request $request)
    {
        $students = User::role('student')->orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $report = null;

        if ($request->filled(['student_id', 'academic_year_id'])) {
            $student = User::find($request->student_id);
            $academicYear = AcademicYear::find($request->academic_year_id);

            $startDate = $request->start_date ?? $academicYear->start_date;
            $endDate = $request->end_date ?? now();

            $attendances = Attendance::with(['subject', 'classRoom'])
                ->forStudent($request->student_id)
                ->forAcademicYear($request->academic_year_id)
                ->forDateRange($startDate, $endDate)
                ->orderBy('date', 'desc')
                ->get();

            $stats = Attendance::getStudentStats(
                $request->student_id,
                $request->academic_year_id,
                $startDate,
                $endDate
            );

            // Group by month
            $monthlyStats = $attendances->groupBy(function ($item) {
                return $item->date->format('Y-m');
            })->map(function ($items) {
                return [
                    'total' => $items->count(),
                    'present' => $items->where('status', 'present')->count(),
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count(),
                    'excused' => $items->where('status', 'excused')->count(),
                ];
            });

            $report = [
                'student' => $student,
                'academic_year' => $academicYear,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'attendances' => $attendances,
                'stats' => $stats,
                'monthly_stats' => $monthlyStats,
            ];
        }

        return view('admin.attendance.student-report', compact('students', 'academicYears', 'report'));
    }

    /**
     * Display class attendance summary report.
     */
    public function classReport(Request $request)
    {
        $classes = ClassRoom::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $report = null;

        if ($request->filled(['class_id', 'academic_year_id'])) {
            $classRoom = ClassRoom::with('students')->find($request->class_id);
            $academicYear = AcademicYear::find($request->academic_year_id);

            $startDate = $request->start_date ?? $academicYear->start_date;
            $endDate = $request->end_date ?? now();

            $studentStats = [];
            foreach ($classRoom->students as $student) {
                $stats = Attendance::getStudentStats(
                    $student->id,
                    $request->academic_year_id,
                    $startDate,
                    $endDate
                );
                $stats['student'] = $student;
                $studentStats[] = $stats;
            }

            // Sort by attendance rate
            usort($studentStats, fn($a, $b) => $b['attendance_rate'] <=> $a['attendance_rate']);

            $report = [
                'class' => $classRoom,
                'academic_year' => $academicYear,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'student_stats' => $studentStats,
            ];
        }

        return view('admin.attendance.class-report', compact('classes', 'academicYears', 'report'));
    }

    /**
     * Display monthly attendance calendar.
     */
    public function calendar(Request $request)
    {
        $classes = ClassRoom::orderBy('name')->get();
        $month = $request->month ?? now()->format('Y-m');

        $calendar = null;

        if ($request->filled('class_id')) {
            $classRoom = ClassRoom::with('students')->find($request->class_id);
            $startOfMonth = Carbon::parse($month)->startOfMonth();
            $endOfMonth = Carbon::parse($month)->endOfMonth();

            $attendances = Attendance::where('class_id', $request->class_id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->get()
                ->groupBy(function ($item) {
                    return $item->date->format('Y-m-d');
                });

            $calendar = [
                'class' => $classRoom,
                'month' => $month,
                'start_of_month' => $startOfMonth,
                'end_of_month' => $endOfMonth,
                'attendances' => $attendances,
            ];
        }

        return view('admin.attendance.calendar', compact('classes', 'month', 'calendar'));
    }
}
