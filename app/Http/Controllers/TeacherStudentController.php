<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BehaviorRecord;
use App\Models\Certificate;
use App\Models\Grade;
use App\Models\SpecialEducationStudent;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Controllers\Concerns\ResolvesTeacherStudents;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Teacher "My Students" — cards #191 / #198.
 *
 * SECURITY: every method resolves the set of student IDs the authenticated
 * teacher actually teaches (via ResolvesTeacherStudents) and aborts 403 if
 * a requested student is not in that set. No cross-school or cross-teacher
 * data leakage is possible.
 */
class TeacherStudentController extends Controller
{
    use HasSchoolScope;
    use ResolvesTeacherStudents;

    // ── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $teacher  = auth()->user();
        $schoolId = $this->activeSchoolId();

        // A non super-admin must operate within a concrete school. Without one,
        // the teaching-students resolver would not be school-scoped, so expose
        // nothing (no null-scope cross-tenant leakage).
        abort_if($schoolId === null && ! $teacher->isSuperAdmin(), 403);

        $allowedIds = $this->teachingStudentIds((int) $teacher->id, $schoolId);

        $query = User::whereIn('id', $allowedIds)
            ->with(['classRoom', 'classRoom.section'])
            ->orderBy('name');

        // Optional name search
        if ($q = $request->input('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%' . $q . '%')
                  ->orWhere('name_ar', 'like', '%' . $q . '%')
                  ->orWhere('name_en', 'like', '%' . $q . '%');
            });
        }

        // Grouped by صف (section) → فصل (class) per card #318, so no pagination.
        $students = $query->get();

        // Quick attendance counts scoped to the current academic year
        $academicYear = AcademicYear::where('is_current', true)
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->first();

        // Attendance % per student, current page only — one grouped query,
        // same present+late/total formula as show(). No record → '—' in the view.
        $studentIds = $students->pluck('id')->all();
        $attendanceRates = Attendance::selectRaw('student_id, status, COUNT(*) as cnt')
            ->whereIn('student_id', $studentIds)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id')
            ->map(function ($rows) {
                $total = $rows->sum('cnt');
                $presentLate = $rows->whereIn('status', ['present', 'late'])->sum('cnt');

                return $total > 0 ? round(($presentLate / $total) * 100, 1) : null;
            });

        // Group students by grade (صف) then class (فصل) for the grouped listing.
        $grouped = $students
            ->groupBy(fn ($s) => optional($s->classRoom?->section)->name ?: __('teacher_students.no_grade'))
            ->map(fn ($inSection) => $inSection->groupBy(fn ($s) => optional($s->classRoom)->name ?: __('teacher_students.no_class')));

        return view('teacher.students.index', compact('students', 'grouped', 'academicYear', 'attendanceRates'));
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function show(int $student): View
    {
        $teacher  = auth()->user();
        $schoolId = $this->activeSchoolId();

        abort_if($schoolId === null && ! $teacher->isSuperAdmin(), 403);

        $allowedIds = $this->teachingStudentIds((int) $teacher->id, $schoolId);

        if (! in_array($student, $allowedIds, true)) {
            abort(403, __('teacher_students.not_your_student'));
        }

        $studentModel = User::with(['classRoom', 'classRoom.section'])->findOrFail($student);

        $academicYear = AcademicYear::where('is_current', true)
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->first();

        // ── Attendance summary ───────────────────────────────────────────────
        $attendanceBase = $studentModel->attendances()
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id));

        $attendanceStats = [
            'total'   => (clone $attendanceBase)->count(),
            'present' => (clone $attendanceBase)->where('status', 'present')->count(),
            'absent'  => (clone $attendanceBase)->where('status', 'absent')->count(),
            'late'    => (clone $attendanceBase)->where('status', 'late')->count(),
            'excused' => (clone $attendanceBase)->where('status', 'excused')->count(),
        ];
        $attendanceStats['rate'] = $attendanceStats['total'] > 0
            ? round((($attendanceStats['present'] + $attendanceStats['late']) / $attendanceStats['total']) * 100, 1)
            : 0;

        // ── Behaviour records ────────────────────────────────────────────────
        $behaviorRecords = BehaviorRecord::with(['behavior', 'action', 'recorder'])
            ->where('subject_user_id', $student)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->latest()
            ->get();

        // ── Special education ────────────────────────────────────────────────
        $specialEd = SpecialEducationStudent::with(['plans', 'notes'])
            ->where('student_id', $student)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->first();

        // ── Certificates (published) ─────────────────────────────────────────
        $certificates = Certificate::published()
            ->where('recipient_user_id', $student)
            ->when($schoolId, fn ($q) => $q->forSchool($schoolId))
            ->orderByDesc('issue_date')
            ->get();

        // ── Grades (published, current year) ─────────────────────────────────
        // $student is already authorised: it is in $allowedIds, which is
        // school-scoped, so these grades belong to this school. (The grades
        // table has no school_id column to scope on directly.)
        $grades = Grade::with('subject')
            ->where('student_id', $student)
            ->where('is_published', true)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->orderBy('semester')
            ->get();

        $gradeAverage = $grades->avg('total');
        $gradeAverage = $gradeAverage !== null ? round($gradeAverage, 1) : null;

        return view('teacher.students.show', compact(
            'studentModel',
            'academicYear',
            'attendanceStats',
            'behaviorRecords',
            'specialEd',
            'certificates',
            'grades',
            'gradeAverage',
        ));
    }
}
