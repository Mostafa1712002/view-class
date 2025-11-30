<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Grade;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $schoolId = $user->school_id;

        $classes = ClassRoom::whereHas('section', fn($q) => $q->where('school_id', $schoolId))
            ->with('section')
            ->get();

        return view('admin.exports.index', compact('classes'));
    }

    public function students(Request $request)
    {
        $user = Auth::user();
        $schoolId = $user->school_id;
        $format = $request->get('format', 'csv');

        $students = User::where('school_id', $schoolId)
            ->whereHas('roles', fn($q) => $q->where('slug', 'student'))
            ->with(['classRoom.section'])
            ->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.exports.pdf.students', compact('students'));
            return $pdf->download('students.pdf');
        }

        $headers = ['الاسم', 'البريد الإلكتروني', 'الهاتف', 'الصف', 'المرحلة', 'تاريخ التسجيل'];
        $data = $students->map(fn($s) => [
            $s->name,
            $s->email,
            $s->phone ?? '-',
            $s->classRoom?->name ?? '-',
            $s->classRoom?->section?->name ?? '-',
            $s->created_at->format('Y/m/d'),
        ]);

        return $this->downloadCsv('students.csv', $headers, $data);
    }

    public function teachers(Request $request)
    {
        $user = Auth::user();
        $schoolId = $user->school_id;
        $format = $request->get('format', 'csv');

        $teachers = User::where('school_id', $schoolId)
            ->whereHas('roles', fn($q) => $q->where('slug', 'teacher'))
            ->with(['subjects'])
            ->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.exports.pdf.teachers', compact('teachers'));
            return $pdf->download('teachers.pdf');
        }

        $headers = ['الاسم', 'البريد الإلكتروني', 'الهاتف', 'المواد', 'تاريخ التسجيل'];
        $data = $teachers->map(fn($t) => [
            $t->name,
            $t->email,
            $t->phone ?? '-',
            $t->subjects->pluck('name')->implode(', '),
            $t->created_at->format('Y/m/d'),
        ]);

        return $this->downloadCsv('teachers.csv', $headers, $data);
    }

    public function grades(Request $request)
    {
        $user = Auth::user();
        $schoolId = $user->school_id;
        $format = $request->get('format', 'csv');
        $classId = $request->get('class_id');

        $query = Grade::whereHas('student', fn($q) => $q->where('school_id', $schoolId))
            ->with(['student', 'subject', 'exam']);

        if ($classId) {
            $query->whereHas('student', fn($q) => $q->where('class_room_id', $classId));
        }

        $grades = $query->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.exports.pdf.grades', compact('grades'));
            return $pdf->download('grades.pdf');
        }

        $headers = ['الطالب', 'المادة', 'الاختبار', 'الدرجة', 'الدرجة القصوى', 'النسبة', 'التاريخ'];
        $data = $grades->map(fn($g) => [
            $g->student?->name ?? '-',
            $g->subject?->name ?? '-',
            $g->exam?->title ?? '-',
            $g->score,
            $g->max_score,
            number_format($g->percentage, 1) . '%',
            $g->created_at->format('Y/m/d'),
        ]);

        return $this->downloadCsv('grades.csv', $headers, $data);
    }

    public function attendance(Request $request)
    {
        $user = Auth::user();
        $schoolId = $user->school_id;
        $format = $request->get('format', 'csv');
        $classId = $request->get('class_id');
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $query = Attendance::whereHas('student', fn($q) => $q->where('school_id', $schoolId))
            ->with(['student.classRoom'])
            ->whereBetween('date', [$dateFrom, $dateTo]);

        if ($classId) {
            $query->whereHas('student', fn($q) => $q->where('class_room_id', $classId));
        }

        $attendance = $query->orderBy('date')->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.exports.pdf.attendance', compact('attendance', 'dateFrom', 'dateTo'));
            return $pdf->download('attendance.pdf');
        }

        $headers = ['التاريخ', 'الطالب', 'الصف', 'الحالة', 'ملاحظات'];
        $data = $attendance->map(fn($a) => [
            $a->date->format('Y/m/d'),
            $a->student?->name ?? '-',
            $a->student?->classRoom?->name ?? '-',
            match($a->status) {
                'present' => 'حاضر',
                'absent' => 'غائب',
                'late' => 'متأخر',
                'excused' => 'معذور',
                default => $a->status,
            },
            $a->notes ?? '-',
        ]);

        return $this->downloadCsv('attendance.csv', $headers, $data);
    }

    private function downloadCsv(string $filename, array $headers, $data)
    {
        $output = "\xEF\xBB\xBF";
        $output .= implode(',', array_map(fn($h) => '"' . $h . '"', $headers)) . "\n";

        foreach ($data as $row) {
            $output .= implode(',', array_map(fn($cell) => '"' . str_replace('"', '""', $cell) . '"', $row)) . "\n";
        }

        return Response::make($output, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
