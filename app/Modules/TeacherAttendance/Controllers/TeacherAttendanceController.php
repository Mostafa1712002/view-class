<?php

namespace App\Modules\TeacherAttendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;
use App\Models\ActivityLog;
use App\Modules\TeacherAttendance\Models\TeacherAttendance;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #264 — Teacher attendance (NET-NEW). Daily + per-period recording + reports.
 */
class TeacherAttendanceController extends Controller
{
    use HasSchoolScope;

    /** Daily teacher attendance (tab 1). */
    public function daily(Request $request): View
    {
        return $this->board($request, 'daily', null);
    }

    /** Per-period teacher attendance (tab 2). */
    public function period(Request $request): View
    {
        $period = $request->filled('period') ? (int) $request->period : null;

        return $this->board($request, 'period', $period);
    }

    private function board(Request $request, string $mode, ?int $period): View
    {
        $schoolId = $this->scopedSchoolId();
        $date     = $request->date ?: now()->format('Y-m-d');

        $teachers = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'teacher'))
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->name.'%'))
            ->when($request->filled('national_id'), fn ($q) => $q->where('national_id', 'like', '%'.$request->national_id.'%'))
            ->orderBy('name')
            ->get();

        $existing = TeacherAttendance::query()
            ->whereDate('date', $date)
            ->when($period !== null, fn ($q) => $q->where('period', $period), fn ($q) => $q->whereNull('period'))
            ->get()->keyBy('teacher_id');

        $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        foreach (TeacherAttendance::whereDate('date', $date)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status') as $s => $c) {
            $counts[$s] = (int) $c;
        }

        $rows = $teachers->map(function ($t) use ($existing) {
            $a = $existing[$t->id] ?? null;

            return [
                'teacher'       => $t,
                'status'        => $a->status ?? null,
                'notes'         => $a->notes ?? null,
                'attendance_id' => $a->id ?? null,
                'present_days'  => TeacherAttendance::where('teacher_id', $t->id)->whereNull('period')->where('status', 'present')->distinct('date')->count('date'),
            ];
        });

        $classes  = ClassRoom::with('section')
            ->when($schoolId !== null, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
            ->orderBy('name')->get();
        $subjects = Subject::when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))->orderBy('name')->get();

        return view('admin.teacher-attendance.board', compact('mode', 'rows', 'counts', 'date', 'period', 'classes', 'subjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'date'                => ['required', 'date'],
            'period'              => ['nullable', 'integer', 'min:1', 'max:12'],
            'class_id'            => ['nullable', 'integer', 'exists:classes,id'],
            'subject_id'          => ['nullable', 'integer', 'exists:subjects,id'],
            'rows'                => ['required', 'array', 'min:1'],
            'rows.*.teacher_id'   => ['required', 'integer', 'exists:users,id'],
            'rows.*.status'       => ['required', 'in:present,absent,late,excused'],
            'rows.*.arrival_time' => ['nullable', 'date_format:H:i'],
            'rows.*.notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $yearId = AcademicYear::where('is_current', true)->value('id');
        $saved  = 0;

        foreach ($data['rows'] as $row) {
            $teacher = User::find($row['teacher_id']);
            if (! $teacher) {
                continue;
            }
            // scope guard: non-super-admin may only record own-school teachers
            if ($schoolId !== null && (int) $teacher->school_id !== $schoolId) {
                continue;
            }

            TeacherAttendance::updateOrCreate(
                [
                    'teacher_id' => $row['teacher_id'],
                    'date'       => $data['date'],
                    'period'     => $data['period'] ?? null,
                ],
                [
                    'school_id'        => $teacher->school_id,
                    'academic_year_id' => $yearId,
                    'class_id'         => $data['class_id'] ?? null,
                    'subject_id'       => $data['subject_id'] ?? null,
                    'status'           => $row['status'],
                    'arrival_time'     => $row['arrival_time'] ?? null,
                    'notes'            => $row['notes'] ?? null,
                    'recorded_by'      => $request->user()->id,
                ]
            );
            $saved++;
        }

        ActivityLog::log('teacher_attendance.record', "تسجيل حضور {$saved} معلم بتاريخ {$data['date']}");

        return back()->with('success', "تم حفظ حضور {$saved} معلم.");
    }

    /** Send a message to a teacher (in-app notification). */
    public function message(Request $request, User $teacher): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId !== null && (int) $teacher->school_id !== $schoolId, 403, 'خارج نطاق صلاحيتك.');

        $request->validate(['message' => ['required', 'string', 'min:3', 'max:1000']]);

        Notification::create([
            'user_id' => $teacher->id,
            'type'    => 'general',
            'title'   => 'رسالة من الإدارة',
            'body'    => $request->message,
            'icon'    => 'bi-envelope',
            'color'   => 'info',
        ]);

        ActivityLog::log('teacher_attendance.message', "إرسال رسالة للمعلم {$teacher->name}");

        return back()->with('success', 'تم إرسال الرسالة للمعلم.');
    }
}
