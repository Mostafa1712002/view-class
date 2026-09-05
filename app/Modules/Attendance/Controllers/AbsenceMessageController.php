<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Modules\Attendance\Actions\SendAttendanceMessageAction;
use App\Modules\QuestionBankCore\Services\QbScopeService;
use App\Modules\SmsServices\Models\SmsAutoMessageSetting;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Models\SmsTemplate;
use App\Modules\SmsServices\Support\SmsTemplateRenderer;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * إدارة رسائل غياب الطالب — message parents of students marked absent (daily,
 * period IS NULL), either automatically after the school day ends or manually
 * (send-all / pick students) via a cascading compound→school→grade→class
 * filter. ~80% assembly: sends route through SendAttendanceMessageAction (the
 * same bridge #262/#271 uses), the body is a school-scoped SmsTemplate, and
 * the auto mode + send_time live on SmsAutoMessageSetting (event_type =
 * absent_daily).
 */
class AbsenceMessageController extends Controller
{
    use HasSchoolScope;

    /**
     * The absence body has no dedicated FK column on SmsAutoMessageSetting
     * (tasks.md only adds send_time) — it's addressed by a fixed, per-school
     * title on SmsTemplate instead of a new column.
     */
    private const TEMPLATE_TITLE = 'رسالة غياب الطالب اليومي';

    public function __construct(private readonly QbScopeService $scope) {}

    public function index(): View
    {
        $schoolId = $this->scopedSchoolId();
        $meta = SmsAutoMessageSetting::EVENT_TYPES['absent_daily'];

        $setting = SmsAutoMessageSetting::query()
            ->where('event_type', 'absent_daily')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->first();

        $template = $schoolId ? $this->absenceTemplate($schoolId) : null;
        $body = $template->body ?? $setting?->template_body ?? $meta['default'];
        $mode = ($setting?->is_enabled ?? false) ? 'auto' : 'manual';
        $sendTime = $setting?->send_time ? substr((string) $setting->send_time, 0, 5) : '';

        return view('admin.attendance.absence-messages.index', [
            'mode'      => $mode,
            'sendTime'  => $sendTime,
            'body'      => $body,
            'variables' => SmsTemplateRenderer::VARIABLES,
            'scopeData' => $this->scopeData($schoolId),
            'today'     => now()->toDateString(),
            'hasSchoolScope' => $schoolId !== null,
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId === null, 422, 'اختر مدرسة من محدّد النطاق أعلى الصفحة أولاً.');

        $data = $request->validate([
            'mode'      => ['required', 'in:auto,manual'],
            'send_time' => ['nullable', 'date_format:H:i'],
        ]);

        if ($data['mode'] === 'auto' && empty($data['send_time'])) {
            return back()->with('error', 'حدد وقت انتهاء اليوم الدراسي لتفعيل الإرسال التلقائي.');
        }

        SmsAutoMessageSetting::updateOrCreate(
            ['school_id' => $schoolId, 'event_type' => 'absent_daily'],
            ['is_enabled' => $data['mode'] === 'auto', 'send_time' => $data['send_time'] ?? null],
        );

        return back()->with('success', 'تم حفظ إعدادات رسائل الغياب.');
    }

    public function saveTemplate(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId === null, 422, 'اختر مدرسة من محدّد النطاق أعلى الصفحة أولاً.');

        $data = $request->validate(['body' => ['required', 'string', 'max:1600']]);

        // withTrashed(): sms_templates has a unique(school_id,title) index that
        // MySQL does NOT exclude soft-deleted rows from, so a previously
        // deleted absence template would otherwise collide on insert here.
        // deleted_at=>null in the update payload restores it in the same query.
        SmsTemplate::withTrashed()->updateOrCreate(
            ['school_id' => $schoolId, 'title' => self::TEMPLATE_TITLE],
            ['body' => $data['body'], 'lang' => 'ar', 'is_active' => true, 'created_by' => auth()->id(), 'deleted_at' => null],
        );

        // Mirror the body into the auto-message row so the generic
        // admin.sms.auto-messages screen (which iterates EVENT_TYPES) shows
        // the same text instead of the event's hardcoded default.
        SmsAutoMessageSetting::updateOrCreate(
            ['school_id' => $schoolId, 'event_type' => 'absent_daily'],
            ['template_body' => $data['body']],
        );

        return back()->with('success', 'تم حفظ نص الرسالة.');
    }

    public function previewAbsent(Request $request): JsonResponse
    {
        $schoolId = $this->scopedSchoolId();
        $request->validate(['date' => ['nullable', 'date']]);
        $date = $request->input('date') ?: now()->toDateString();

        $rows = $this->absentQuery(
            $schoolId,
            $date,
            $this->intArray($request->input('school_ids')),
            $this->intArray($request->input('grade_levels')),
            $this->intArray($request->input('class_ids')),
        )->with(['student:id,name,national_id', 'classRoom:id,name'])->get();

        return response()->json([
            'students' => $rows->map(fn (Attendance $a) => [
                'attendance_id' => $a->id,
                'student_id'    => $a->student_id,
                'name'          => $a->student?->name,
                'national_id'   => $a->student?->national_id,
                'class_name'    => $a->classRoom?->name,
                'status'        => 'غائب', // absent-only query — never present
            ])->values(),
        ]);
    }

    public function send(Request $request, SendAttendanceMessageAction $sender): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'date'          => ['nullable', 'date'],
            'school_ids'    => ['array'],
            'school_ids.*'  => ['integer'],
            'grade_levels'  => ['array'],
            'grade_levels.*'=> ['integer'],
            'class_ids'     => ['array'],
            'class_ids.*'   => ['integer'],
            'student_ids'   => ['array'], // "تحديد الطلاب" subset; empty = "إرسال الكل"
            'student_ids.*' => ['integer'],
            'channel'       => ['nullable', 'in:in_app,sms,whatsapp'],
        ]);

        $date = $data['date'] ?? now()->toDateString();

        $rows = $this->absentQuery($schoolId, $date, $data['school_ids'] ?? [], $data['grade_levels'] ?? [], $data['class_ids'] ?? [])
            ->when(! empty($data['student_ids']), fn (Builder $q) => $q->whereIn('student_id', $data['student_ids']))
            ->with(['student.parents', 'classRoom.section'])
            ->get();

        if ($rows->isEmpty()) {
            return back()->with('error', 'لا يوجد طلاب غائبون ضمن هذا الفلتر.');
        }

        $result = ['students' => 0, 'parents' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($rows->groupBy(fn (Attendance $a) => (int) optional($a->classRoom?->section)->school_id) as $sid => $schoolRows) {
            $sid = (int) $sid;
            $body = $this->absenceTemplate($sid)->body
                ?? SmsAutoMessageSetting::query()->where('school_id', $sid)->where('event_type', 'absent_daily')->value('template_body')
                ?? SmsAutoMessageSetting::EVENT_TYPES['absent_daily']['default'];
            $channel = $data['channel'] ?? $this->resolveChannel($sid);

            // Grouped per class so {class}/{grade} render correctly per recipient.
            foreach ($schoolRows->groupBy('class_id') as $classRows) {
                $classRoom = $classRows->first()->classRoom;
                $students = $classRows->pluck('student')->filter()->unique('id')->values();

                $r = $sender->execute(
                    schoolId: $sid,
                    students: $students,
                    channel: $channel,
                    templateId: null,
                    bodyTemplate: $body,
                    senderUserId: auth()->id(),
                    extraVars: [
                        'date'             => $date,
                        'attendance_state' => 'غائب',
                        'class'            => $classRoom?->name ?? '',
                        'grade'            => $this->gradeLabel((int) ($classRoom?->grade_level ?? 0)),
                    ],
                );
                foreach ($result as $k => $_) {
                    $result[$k] += $r[$k];
                }
            }

            // Dedupe: mark every attempted row (incl. no-phone skips) so the
            // hourly auto command never retries them for this date.
            Attendance::whereIn('id', $schoolRows->pluck('id'))->update(['notified_parent' => true]);
        }

        ActivityLog::log('attendance.absence_messages', "إرسال رسائل غياب ({$channel}) — {$result['students']} طالب");

        return back()->with('success', $this->resultMessage($result));
    }

    /**
     * @return array{compounds: \Illuminate\Support\Collection, schools: \Illuminate\Support\Collection, classes: \Illuminate\Support\Collection, gradeLabels: array<int,string>}
     */
    private function scopeData(?int $schoolId): array
    {
        $tree = $this->scope->schoolTree($schoolId);
        $schools = $tree['compounds']->flatMap(fn ($g) => $g['schools'])->merge($tree['ungrouped']);

        $classes = collect();
        foreach ($schools as $school) {
            foreach ($this->scope->classesForSchool($school->id) as $class) {
                $classes->push(['id' => $class->id, 'name' => $class->name, 'grade_level' => $class->grade_level, 'school_id' => $school->id]);
            }
        }

        return [
            'compounds'   => $tree['compounds']->map(fn ($g) => ['id' => $g['compound']->id, 'name' => $g['compound']->name_ar])->values(),
            'schools'     => $schools->map(fn ($s) => ['id' => $s->id, 'name' => $s->name_ar ?: $s->name, 'compound_id' => $s->compound_id ?? null])->values(),
            'classes'     => $classes->values(),
            'gradeLabels' => collect(range(1, 12))->mapWithKeys(fn ($g) => [$g => $this->gradeLabel($g)]),
        ];
    }

    private function absentQuery(?int $schoolId, string $date, array $schoolIds, array $gradeLevels, array $classIds): Builder
    {
        return Attendance::query()->absent()->whereNull('period')->whereDate('date', $date)
            ->when($schoolId !== null, fn (Builder $q) => $q->whereHas('classRoom.section', fn (Builder $s) => $s->where('school_id', $schoolId)))
            ->when($schoolId === null && $schoolIds !== [], fn (Builder $q) => $q->whereHas('classRoom.section', fn (Builder $s) => $s->whereIn('school_id', $schoolIds)))
            ->when($gradeLevels !== [], fn (Builder $q) => $q->whereHas('classRoom', fn (Builder $c) => $c->whereIn('grade_level', $gradeLevels)))
            ->when($classIds !== [], fn (Builder $q) => $q->whereIn('class_id', $classIds));
    }

    private function absenceTemplate(int $schoolId): ?SmsTemplate
    {
        return SmsTemplate::query()->where('school_id', $schoolId)->where('title', self::TEMPLATE_TITLE)->first();
    }

    /** whatsapp if enabled else sms (a usable sender exists) else in_app. */
    private function resolveChannel(int $schoolId): string
    {
        if (SchoolWhatsappSetting::where('school_id', $schoolId)->value('is_enabled')) {
            return 'whatsapp';
        }
        if (SmsSender::query()->where('school_id', $schoolId)->usable()->exists()) {
            return 'sms';
        }

        return 'in_app';
    }

    private function gradeLabel(int $grade): string
    {
        $names = [1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الخامس', 6 => 'السادس',
            7 => 'السابع', 8 => 'الثامن', 9 => 'التاسع', 10 => 'العاشر', 11 => 'الحادي عشر', 12 => 'الثاني عشر'];

        return $names[$grade] ?? "الصف {$grade}";
    }

    /** @return int[] */
    private function intArray(mixed $value): array
    {
        return array_map('intval', (array) ($value ?? []));
    }

    private function resultMessage(array $r): string
    {
        $parts = [];
        if ($r['sent'])    $parts[] = "{$r['sent']} مُرسلة";
        if ($r['queued'])  $parts[] = "{$r['queued']} بانتظار الإرسال";
        if ($r['failed'])  $parts[] = "{$r['failed']} فشلت";
        if ($r['skipped']) $parts[] = "{$r['skipped']} متخطّاة";

        return $parts ? implode(' · ', $parts) : 'لا يوجد مستلمون.';
    }
}
