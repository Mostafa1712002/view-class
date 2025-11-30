<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\User;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    /**
     * Display grade entry interface.
     */
    public function index(Request $request)
    {
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassRoom::with('students')->orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $grades = collect();
        $selectedClass = null;
        $selectedSubject = null;
        $selectedYear = null;
        $selectedSemester = null;

        if ($request->filled(['class_id', 'subject_id', 'academic_year_id', 'semester'])) {
            $selectedClass = ClassRoom::with('students')->find($request->class_id);
            $selectedSubject = Subject::find($request->subject_id);
            $selectedYear = AcademicYear::find($request->academic_year_id);
            $selectedSemester = $request->semester;

            // Get all students in the class with their grades
            $students = $selectedClass->students;

            foreach ($students as $student) {
                $grade = Grade::firstOrNew([
                    'student_id' => $student->id,
                    'subject_id' => $request->subject_id,
                    'class_id' => $request->class_id,
                    'academic_year_id' => $request->academic_year_id,
                    'semester' => $request->semester,
                ], [
                    'teacher_id' => Auth::id(),
                ]);

                $grade->student = $student;
                $grades->push($grade);
            }
        }

        return view('admin.grades.index', compact(
            'subjects',
            'classes',
            'academicYears',
            'grades',
            'selectedClass',
            'selectedSubject',
            'selectedYear',
            'selectedSemester'
        ));
    }

    /**
     * Save grades for multiple students.
     */
    public function store(Request $request)
    {
        $request->validate([
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:users,id',
            'grades.*.quiz_avg' => 'nullable|numeric|min:0|max:100',
            'grades.*.homework_avg' => 'nullable|numeric|min:0|max:100',
            'grades.*.midterm' => 'nullable|numeric|min:0|max:100',
            'grades.*.final' => 'nullable|numeric|min:0|max:100',
            'grades.*.participation' => 'nullable|numeric|min:0|max:100',
            'grades.*.comments' => 'nullable|string',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:first,second',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->grades as $gradeData) {
                $grade = Grade::updateOrCreate(
                    [
                        'student_id' => $gradeData['student_id'],
                        'subject_id' => $request->subject_id,
                        'class_id' => $request->class_id,
                        'academic_year_id' => $request->academic_year_id,
                        'semester' => $request->semester,
                    ],
                    [
                        'teacher_id' => Auth::id(),
                        'quiz_avg' => $gradeData['quiz_avg'] ?? null,
                        'homework_avg' => $gradeData['homework_avg'] ?? null,
                        'midterm' => $gradeData['midterm'] ?? null,
                        'final' => $gradeData['final'] ?? null,
                        'participation' => $gradeData['participation'] ?? null,
                        'comments' => $gradeData['comments'] ?? null,
                    ]
                );

                // Calculate total and letter grade
                $grade->calculateTotal();
            }
        });

        return back()->with('success', 'تم حفظ الدرجات بنجاح.');
    }

    /**
     * Publish grades for a class.
     */
    public function publish(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:first,second',
        ]);

        Grade::where('subject_id', $request->subject_id)
            ->where('class_id', $request->class_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('semester', $request->semester)
            ->update(['is_published' => true]);

        return back()->with('success', 'تم نشر الدرجات بنجاح.');
    }

    /**
     * Unpublish grades for a class.
     */
    public function unpublish(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:first,second',
        ]);

        Grade::where('subject_id', $request->subject_id)
            ->where('class_id', $request->class_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->where('semester', $request->semester)
            ->update(['is_published' => false]);

        return back()->with('success', 'تم إلغاء نشر الدرجات.');
    }

    /**
     * Display class report.
     */
    public function classReport(Request $request)
    {
        $classes = ClassRoom::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $report = null;

        if ($request->filled(['class_id', 'academic_year_id', 'semester'])) {
            $classRoom = ClassRoom::with('students')->find($request->class_id);

            $grades = Grade::with(['student', 'subject'])
                ->where('class_id', $request->class_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('semester', $request->semester)
                ->where('is_published', true)
                ->get();

            // Organize grades by student
            $studentGrades = [];
            foreach ($grades as $grade) {
                $studentId = $grade->student_id;
                if (!isset($studentGrades[$studentId])) {
                    $studentGrades[$studentId] = [
                        'student' => $grade->student,
                        'grades' => [],
                        'total_avg' => 0,
                        'subjects_count' => 0,
                    ];
                }
                $studentGrades[$studentId]['grades'][] = $grade;
                if ($grade->total !== null) {
                    $studentGrades[$studentId]['total_avg'] += $grade->total;
                    $studentGrades[$studentId]['subjects_count']++;
                }
            }

            // Calculate averages and rank
            foreach ($studentGrades as &$data) {
                $data['average'] = $data['subjects_count'] > 0
                    ? $data['total_avg'] / $data['subjects_count']
                    : 0;
            }

            // Sort by average descending
            usort($studentGrades, fn($a, $b) => $b['average'] <=> $a['average']);

            // Add rank
            $rank = 1;
            foreach ($studentGrades as &$data) {
                $data['rank'] = $rank++;
            }

            $report = [
                'class' => $classRoom,
                'academic_year' => AcademicYear::find($request->academic_year_id),
                'semester' => $request->semester,
                'students' => $studentGrades,
                'subjects' => Subject::whereIn('id', $grades->pluck('subject_id')->unique())->get(),
            ];
        }

        return view('admin.grades.class-report', compact('classes', 'academicYears', 'report'));
    }

    /**
     * Display student report.
     */
    public function studentReport(Request $request)
    {
        $students = User::role('student')->orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $report = null;

        if ($request->filled(['student_id', 'academic_year_id'])) {
            $student = User::find($request->student_id);

            $grades = Grade::with(['subject', 'teacher', 'classRoom'])
                ->where('student_id', $request->student_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('is_published', true)
                ->get();

            $firstSemester = $grades->where('semester', 'first');
            $secondSemester = $grades->where('semester', 'second');

            $report = [
                'student' => $student,
                'academic_year' => AcademicYear::find($request->academic_year_id),
                'first_semester' => [
                    'grades' => $firstSemester,
                    'average' => $firstSemester->avg('total'),
                    'passed' => $firstSemester->where('total', '>=', 60)->count(),
                    'failed' => $firstSemester->where('total', '<', 60)->count(),
                ],
                'second_semester' => [
                    'grades' => $secondSemester,
                    'average' => $secondSemester->avg('total'),
                    'passed' => $secondSemester->where('total', '>=', 60)->count(),
                    'failed' => $secondSemester->where('total', '<', 60)->count(),
                ],
                'overall_average' => $grades->avg('total'),
            ];
        }

        return view('admin.grades.student-report', compact('students', 'academicYears', 'report'));
    }

    /**
     * Display subject report.
     */
    public function subjectReport(Request $request)
    {
        $subjects = Subject::orderBy('name')->get();
        $classes = ClassRoom::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $report = null;

        if ($request->filled(['subject_id', 'class_id', 'academic_year_id', 'semester'])) {
            $grades = Grade::with(['student'])
                ->where('subject_id', $request->subject_id)
                ->where('class_id', $request->class_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('semester', $request->semester)
                ->where('is_published', true)
                ->orderBy('total', 'desc')
                ->get();

            $gradeDistribution = [
                'A+' => $grades->where('letter_grade', 'A+')->count(),
                'A' => $grades->where('letter_grade', 'A')->count(),
                'B+' => $grades->where('letter_grade', 'B+')->count(),
                'B' => $grades->where('letter_grade', 'B')->count(),
                'C+' => $grades->where('letter_grade', 'C+')->count(),
                'C' => $grades->where('letter_grade', 'C')->count(),
                'D+' => $grades->where('letter_grade', 'D+')->count(),
                'D' => $grades->where('letter_grade', 'D')->count(),
                'F' => $grades->where('letter_grade', 'F')->count(),
            ];

            $report = [
                'subject' => Subject::find($request->subject_id),
                'class' => ClassRoom::find($request->class_id),
                'academic_year' => AcademicYear::find($request->academic_year_id),
                'semester' => $request->semester,
                'grades' => $grades,
                'statistics' => [
                    'total_students' => $grades->count(),
                    'average' => $grades->avg('total'),
                    'highest' => $grades->max('total'),
                    'lowest' => $grades->min('total'),
                    'passed' => $grades->where('total', '>=', 60)->count(),
                    'failed' => $grades->where('total', '<', 60)->count(),
                    'pass_rate' => $grades->count() > 0
                        ? ($grades->where('total', '>=', 60)->count() / $grades->count()) * 100
                        : 0,
                ],
                'distribution' => $gradeDistribution,
            ];
        }

        return view('admin.grades.subject-report', compact('subjects', 'classes', 'academicYears', 'report'));
    }

    /**
     * Export grades to PDF.
     */
    public function exportPdf(Request $request)
    {
        // PDF export implementation would go here
        // Using a library like DomPDF or Snappy
        return back()->with('info', 'سيتم إضافة تصدير PDF قريباً.');
    }
}
