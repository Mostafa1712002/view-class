<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Modules\Attendance\Repositories\Contracts\AttendanceRepository;
use App\Modules\Attendance\Services\AttendanceQueryService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #261 — Student attendance (daily + per-period) recording + display.
 *
 * Extends the existing Attendance module. Writes go through
 * EloquentAttendanceRepository::saveWithNotify so the absence-notify +
 * excuse flow built in #260 keeps working.
 */
class StudentAttendanceController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly AttendanceRepository $repo,
        private readonly AttendanceQueryService $query,
    ) {}

    /** Daily attendance screen (tab 1). */
    public function daily(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();

        return $this->renderBoard($request, $schoolId, 'daily', null);
    }

    /** Per-period attendance screen (tab 2). */
    public function period(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $period   = $request->filled('period') ? (int) $request->period : null;

        return $this->renderBoard($request, $schoolId, 'period', $period);
    }

    private function renderBoard(Request $request, ?int $schoolId, string $mode, ?int $period): View
    {
        $classes  = $this->query->classesForScope($schoolId);
        $subjects = $this->query->subjectsForScope($schoolId);
        $date     = $request->date ?: now()->format('Y-m-d');

        $rows        = collect();
        $counts      = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        $selectedClass = null;

        if ($request->filled('class_id')) {
            $selectedClass = $classes->firstWhere('id', (int) $request->class_id);
            if ($selectedClass) {
                $students = $this->query->studentsForClass(
                    (int) $request->class_id,
                    $schoolId,
                    ['name' => $request->name, 'national_id' => $request->national_id],
                );
                $existing = $this->query->existingRows((int) $request->class_id, $date, $period);

                $rows = $students->map(function ($student) use ($existing) {
                    $att = $existing[$student->id] ?? null;

                    return [
                        'student'       => $student,
                        'status'        => $att->status ?? null,
                        'attendance_id' => $att->id ?? null,
                        'notes'         => $att->notes ?? null,
                        'excuse_status' => $att->excuse_status ?? null,
                        'excuse_text'   => $att->excuse_text ?? null,
                        'present_days'  => $this->query->presentDaysCount($student->id),
                    ];
                });

                $counts = $this->query->statusCounts((int) $request->class_id, $date, $period);
            }
        }

        return view('admin.attendance.students.board', [
            'mode'          => $mode,
            'classes'       => $classes,
            'subjects'      => $subjects,
            'rows'          => $rows,
            'counts'        => $counts,
            'selectedClass' => $selectedClass,
            'date'          => $date,
            'period'        => $period,
            'filters'       => $request->only(['name', 'national_id', 'subject_id', 'period']),
        ]);
    }

    /**
     * Persist one student's status (individual quick action) or the whole
     * board. Gated by the matching granular write permission.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'class_id'             => ['required', 'integer', 'exists:classes,id'],
            'date'                 => ['required', 'date'],
            'period'               => ['nullable', 'integer', 'min:1', 'max:12'],
            'subject_id'           => ['nullable', 'integer', 'exists:subjects,id'],
            'rows'                 => ['required', 'array', 'min:1'],
            'rows.*.student_id'    => ['required', 'integer', 'exists:users,id'],
            'rows.*.status'        => ['required', 'in:present,absent,late,excused'],
            'rows.*.arrival_time'  => ['nullable', 'date_format:H:i'],
            'rows.*.notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        $class = ClassRoom::with('section')->findOrFail($data['class_id']);
        abort_unless($this->query->classInScope($class, $schoolId), 403, 'هذا الفصل خارج نطاق صلاحيتك.');

        // Each row's student must actually belong to this class (no cross-class/tenant write).
        $allowedIds = $class->students()->pluck('users.id')->all();
        foreach ($data['rows'] as $row) {
            abort_unless(in_array((int) $row['student_id'], $allowedIds, true), 403, 'طالب خارج هذا الفصل.');
        }

        $yearId = $this->query->currentAcademicYearId();
        $recorder = $request->user();
        $saved = 0;
        $ids = []; // student_id => attendance_id, for inline AJAX (no-row excuse case)

        foreach ($data['rows'] as $row) {
            $attendance = $this->repo->saveWithNotify([
                'student_id'       => $row['student_id'],
                'class_id'         => $data['class_id'],
                'subject_id'       => $data['subject_id'] ?? null,
                'academic_year_id' => $yearId,
                'date'             => $data['date'],
                'period'           => $data['period'] ?? null,
                'status'           => $row['status'],
                'arrival_time'     => $row['arrival_time'] ?? null,
                'notes'            => $row['notes'] ?? null,
            ], $recorder);
            $ids[(int) $row['student_id']] = $attendance->id;
            $saved++;
        }

        ActivityLog::log('attendance.record', "تسجيل حضور {$saved} طالب — فصل {$class->name} بتاريخ {$data['date']}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'ids' => $ids, 'message' => "تم حفظ الحضور لـ {$saved} طالب."]);
        }

        return back()->with('success', "تم حفظ الحضور لـ {$saved} طالب بنجاح.");
    }

    /** Bulk: apply one status to selected students. */
    public function bulk(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'class_id'     => ['required', 'integer', 'exists:classes,id'],
            'date'         => ['required', 'date'],
            'period'       => ['nullable', 'integer', 'min:1', 'max:12'],
            'subject_id'   => ['nullable', 'integer', 'exists:subjects,id'],
            'status'       => ['required', 'in:present,absent,late,excused'],
            'student_ids'  => ['required', 'array', 'min:1'],
            'student_ids.*'=> ['integer', 'exists:users,id'],
        ]);

        $class = ClassRoom::with('section')->findOrFail($data['class_id']);
        abort_unless($this->query->classInScope($class, $schoolId), 403, 'هذا الفصل خارج نطاق صلاحيتك.');

        // Selected students must belong to this class (no cross-class/tenant write).
        $allowedIds = $class->students()->pluck('users.id')->all();
        foreach ($data['student_ids'] as $sid) {
            abort_unless(in_array((int) $sid, $allowedIds, true), 403, 'طالب خارج هذا الفصل.');
        }

        $yearId = $this->query->currentAcademicYearId();
        $recorder = $request->user();

        foreach ($data['student_ids'] as $sid) {
            $this->repo->saveWithNotify([
                'student_id'       => $sid,
                'class_id'         => $data['class_id'],
                'subject_id'       => $data['subject_id'] ?? null,
                'academic_year_id' => $yearId,
                'date'             => $data['date'],
                'period'           => $data['period'] ?? null,
                'status'           => $data['status'],
            ], $recorder);
        }

        $count = count($data['student_ids']);
        ActivityLog::log('attendance.bulk', "تسجيل جماعي ({$data['status']}) لـ {$count} طالب — فصل {$class->name}");

        return back()->with('success', "تم تطبيق الحالة على {$count} طالب.");
    }

    /** Add / edit a note on an existing attendance row. */
    public function addNote(Request $request, Attendance $attendance): RedirectResponse|JsonResponse
    {
        $this->authorizeRow($attendance);
        // nullable so clearing a note inline matches the board-save path (store uses nullable).
        $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $old = $attendance->only('notes');
        $attendance->update(['notes' => $request->notes]);
        ActivityLog::logUpdate($attendance, 'إضافة ملاحظة على سجل حضور', $old);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حفظ الملاحظة.']);
        }

        return back()->with('success', 'تم حفظ الملاحظة.');
    }

    /** Attach an excuse to an absence/late row (staff side). */
    public function addExcuse(Request $request, Attendance $attendance): RedirectResponse|JsonResponse
    {
        $this->authorizeRow($attendance);
        $request->validate(['excuse_text' => ['required', 'string', 'min:3', 'max:1000']]);
        $attendance->update([
            'status'              => 'excused',
            'excuse_status'       => 'accepted',
            'excuse_text'         => $request->excuse_text,
            'excuse_submitted_at' => now(),
            'excuse_reviewed_at'  => now(),
            'excuse_reviewed_by'  => $request->user()->id,
        ]);
        ActivityLog::log('attendance.excuse', 'إضافة عذر مقبول لسجل حضور', $attendance);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'تم إضافة العذر وتعيين الحالة كمستأذن.']);
        }

        return back()->with('success', 'تم إضافة العذر وتعيين الحالة كمستأذن.');
    }

    private function authorizeRow(Attendance $attendance): void
    {
        $schoolId = $this->scopedSchoolId();
        if ($schoolId === null) {
            return; // super-admin see-all
        }
        $student = $attendance->student;
        abort_if((int) optional($student)->school_id !== $schoolId, 403, 'هذا السجل خارج نطاق صلاحيتك.');
    }
}
