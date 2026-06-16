<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Modules\Attendance\Actions\SendAttendanceMessageAction;
use App\Modules\Attendance\Services\AttendanceQueryService;
use App\Modules\SmsServices\Models\SmsTemplate;
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
     * Send a follow-up message to the parent. #271 — sms/whatsapp now route
     * through the real messaging layer (SmsServices / Whatsapp) via
     * SendAttendanceMessageAction; in_app records the canonical Notification.
     * A chosen SMS template (template_id) supplies the body when picked.
     */
    public function notify(Request $request, Attendance $attendance, SendAttendanceMessageAction $sender): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $student  = $attendance->student;
        abort_if($schoolId !== null && (int) optional($student)->school_id !== $schoolId, 403, 'خارج نطاق صلاحيتك.');

        $request->validate([
            'channel'     => ['required', 'in:in_app,sms,whatsapp'],
            'message'     => ['required', 'string', 'min:3', 'max:1000'],
            'template_id' => ['nullable', 'integer'],
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

        $statusMap = ['present' => 'حاضر', 'absent' => 'غائب', 'late' => 'متأخر', 'excused' => 'بعذر'];
        $result = $sender->execute(
            schoolId: (int) ($student->school_id ?? $schoolId ?? 0),
            students: [$student],
            channel: $request->channel,
            templateId: $request->filled('template_id') ? (int) $request->template_id : null,
            bodyTemplate: $request->message,
            senderUserId: auth()->id(),
            extraVars: [
                'date'             => optional($attendance->date)->format('Y-m-d') ?? now()->format('Y-m-d'),
                'attendance_state' => $statusMap[$attendance->status] ?? $attendance->status,
            ],
        );

        $attendance->update(['notified_parent' => true]);
        ActivityLog::log('attendance.notify', "إرسال رسالة غياب لولي الأمر ({$request->channel}) — الطالب {$student->name}", $attendance);

        return back()->with('success', $this->resultMessage($result, $request->channel));
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

        // #271 — message templates picker (school-scoped, active only).
        $templates = SmsTemplate::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'body']);

        return view('admin.attendance.follow-up.user-reports', compact('classes', 'students', 'templates') + [
            'selectedClass' => $request->class_id,
        ]);
    }

    /** Send composed message to selected students' parents (#271 — real layer). */
    public function sendUserReports(Request $request, SendAttendanceMessageAction $sender): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'student_ids'  => ['required', 'array', 'min:1'],
            'student_ids.*'=> ['integer', 'exists:users,id'],
            'channel'      => ['required', 'in:in_app,sms,whatsapp'],
            'message'      => ['required', 'string', 'min:3', 'max:1000'],
            'template_id'  => ['nullable', 'integer'],
        ]);

        $students = \App\Models\User::whereIn('id', $data['student_ids'])
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->with('parents')
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'لا يوجد طلاب ضمن نطاق صلاحيتك.');
        }

        // group by school so each batch is correctly scoped (super-admin null).
        $result = ['students' => 0, 'parents' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($students->groupBy('school_id') as $sid => $group) {
            $r = $sender->execute(
                schoolId: (int) $sid,
                students: $group,
                channel: $data['channel'],
                templateId: $data['template_id'] ?? null,
                bodyTemplate: $data['message'],
                senderUserId: auth()->id(),
            );
            foreach ($result as $k => $_) {
                $result[$k] += $r[$k];
            }
        }

        ActivityLog::log('attendance.user_reports', "إرسال تقرير للمستخدمين ({$data['channel']}) — {$result['students']} طالب");

        return back()->with('success', $this->resultMessage($result, $data['channel']));
    }

    /** Build a human Arabic summary of a send result. */
    private function resultMessage(array $r, string $channel): string
    {
        if ($channel === 'in_app') {
            return "تم إرسال الرسالة إلى {$r['parents']} ولي أمر داخل المنصة.";
        }

        $parts = [];
        if ($r['sent'])    $parts[] = "{$r['sent']} مُرسلة";
        if ($r['queued'])  $parts[] = "{$r['queued']} بانتظار الإرسال";
        if ($r['failed'])  $parts[] = "{$r['failed']} فشلت";
        if ($r['skipped']) $parts[] = "{$r['skipped']} متخطّاة";

        $label = $channel === 'sms' ? 'SMS' : 'واتساب';
        $summary = $parts ? implode(' · ', $parts) : 'لا يوجد مستلمون';

        return "رسائل {$label}: {$summary}.";
    }
}
