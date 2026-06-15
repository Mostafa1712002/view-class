<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Notification;
use App\Modules\Attendance\Services\AttendanceQueryService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #262 — Late / absence follow-up + per-user (parent) reports/messaging.
 */
class FollowUpController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly AttendanceQueryService $query) {}

    /** Follow-up board: filterable absence/late/excuse list with contact status. */
    public function index(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $classes  = $this->query->classesForScope($schoolId);
        $subjects = $this->query->subjectsForScope($schoolId);

        $rows   = collect();
        $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];

        // Only run the heavy query when at least a date filter is present.
        $date = $request->date;

        $base = Attendance::query()
            ->with(['student.parents', 'classRoom', 'subject'])
            ->when($schoolId !== null, function (Builder $q) use ($schoolId) {
                $q->whereHas('classRoom.section', fn (Builder $s) => $s->where('school_id', $schoolId));
            })
            ->when($date, fn ($q) => $q->whereDate('date', $date))
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', (int) $request->class_id))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', (int) $request->subject_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('name'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%'.$request->name.'%')))
            ->when($request->filled('national_id'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('national_id', 'like', '%'.$request->national_id.'%')));

        // Type filter: daily/period absence/late/excuse
        if ($request->filled('type')) {
            $base = match ($request->type) {
                'absent_daily'  => $base->where('status', 'absent')->whereNull('period'),
                'absent_period' => $base->where('status', 'absent')->whereNotNull('period'),
                'late_daily'    => $base->where('status', 'late')->whereNull('period'),
                'late_period'   => $base->where('status', 'late')->whereNotNull('period'),
                'excuse'        => $base->where('status', 'excused'),
                default         => $base,
            };
        } else {
            // Follow-up board only cares about non-present rows by default
            $base = $base->whereIn('status', ['absent', 'late', 'excused']);
        }

        if ($date || $request->filled('class_id') || $request->filled('status') || $request->filled('type')) {
            $rows = $base->orderByDesc('date')->paginate(25)->withQueryString();

            // counts (scoped, for the date if provided)
            $cBase = Attendance::query()
                ->when($schoolId !== null, fn (Builder $q) => $q->whereHas('classRoom.section', fn (Builder $s) => $s->where('school_id', $schoolId)))
                ->when($date, fn ($q) => $q->whereDate('date', $date));
            foreach (['present', 'absent', 'late', 'excused'] as $s) {
                $counts[$s] = (clone $cBase)->where('status', $s)->count();
            }
        }

        return view('admin.attendance.follow-up.index', compact('rows', 'counts', 'classes', 'subjects') + [
            'filters' => $request->all(),
        ]);
    }

    /**
     * Send a follow-up message to the parent (in-app notification — the real
     * SMS/WhatsApp send reuses the existing channel services; this records the
     * canonical in-app notification + the contact flag).
     */
    public function notify(Request $request, Attendance $attendance): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $student  = $attendance->student;
        abort_if($schoolId !== null && (int) optional($student)->school_id !== $schoolId, 403, 'خارج نطاق صلاحيتك.');

        $request->validate([
            'channel' => ['required', 'in:in_app,sms,whatsapp'],
            'message' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if (! $student) {
            return back()->with('error', 'الطالب غير موجود.');
        }

        // Acceptance criterion: do not send without a valid parent phone for
        // SMS/WhatsApp channels.
        $parents = $student->parents()->wherePivot('can_receive_notifications', true)->get();
        if ($request->channel !== 'in_app') {
            $hasPhone = $parents->contains(fn ($p) => ! empty($p->phone));
            if (! $hasPhone) {
                return back()->with('error', 'لا يوجد رقم جوال صحيح لولي الأمر لإرسال الرسالة.');
            }
        }

        $sent = 0;
        foreach ($parents as $parent) {
            Notification::create([
                'user_id'     => $parent->id,
                'type'        => 'attendance_alert',
                'title'       => 'إشعار حضور وغياب',
                'body'        => $request->message,
                'icon'        => 'bi-bell',
                'color'       => 'warning',
                'data'        => ['attendance_id' => $attendance->id, 'channel' => $request->channel],
            ]);
            $sent++;
        }

        $attendance->update(['notified_parent' => true]);
        ActivityLog::log('attendance.notify', "إرسال رسالة غياب لولي الأمر ({$request->channel}) — الطالب {$student->name}", $attendance);

        return back()->with('success', $sent > 0 ? "تم إرسال الرسالة إلى {$sent} ولي أمر." : 'لا يوجد أولياء أمور مرتبطون.');
    }

    /** "تقارير المستخدمين" — multi-select message composer screen. */
    public function userReports(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $classes  = $this->query->classesForScope($schoolId);

        $students = collect();
        if ($request->filled('class_id')) {
            $students = $this->query->studentsForClass((int) $request->class_id, $schoolId);
        }

        return view('admin.attendance.follow-up.user-reports', compact('classes', 'students') + [
            'selectedClass' => $request->class_id,
        ]);
    }

    /** Send composed message to selected students' parents (records result). */
    public function sendUserReports(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'student_ids'  => ['required', 'array', 'min:1'],
            'student_ids.*'=> ['integer', 'exists:users,id'],
            'channel'      => ['required', 'in:in_app,sms,whatsapp'],
            'message'      => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $students = \App\Models\User::whereIn('id', $data['student_ids'])
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->with('parents')
            ->get();

        $sent = 0; $skipped = 0;
        foreach ($students as $student) {
            $parents = $student->parents()->wherePivot('can_receive_notifications', true)->get();
            if ($data['channel'] !== 'in_app' && ! $parents->contains(fn ($p) => ! empty($p->phone))) {
                $skipped++;
                continue;
            }
            foreach ($parents as $parent) {
                Notification::create([
                    'user_id' => $parent->id,
                    'type'    => 'attendance_alert',
                    'title'   => 'رسالة من المدرسة',
                    'body'    => $data['message'],
                    'icon'    => 'bi-envelope',
                    'color'   => 'info',
                    'data'    => ['channel' => $data['channel'], 'student_id' => $student->id],
                ]);
            }
            $sent++;
        }

        ActivityLog::log('attendance.user_reports', "إرسال تقرير للمستخدمين ({$data['channel']}) — {$sent} طالب");

        $msg = "تم الإرسال إلى أولياء أمور {$sent} طالب.";
        if ($skipped) $msg .= " تم تخطي {$skipped} لعدم وجود رقم جوال.";

        return back()->with('success', $msg);
    }
}
