<?php

namespace App\Modules\SchoolCalendar\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Assignment;
use App\Models\Exam;
use App\Models\SchedulePeriod;
use App\Models\VirtualClass;
use App\Modules\SchoolCalendar\Repositories\Contracts\SchoolEventRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Interactive calendar for the authenticated teacher. Aggregates the teacher's
 * own items across six sources into one FullCalendar (v3) feed:
 *   الحصص (schedule lessons) · الاختبارات · الواجبات · الفصول الافتراضية ·
 *   المواعيد · أحداث التقويم المدرسي.
 *
 * Mirrors MyCalendarController's direct-query aggregation style (same module).
 */
class TeacherCalendarController extends Controller
{
    use HasSchoolScope;

    /** Source colours (Bootstrap-4 palette friendly). */
    private const COLOR_LESSON        = '#10b981';
    private const COLOR_EXAM          = '#ef4444';
    private const COLOR_ASSIGNMENT    = '#f59e0b';
    private const COLOR_VIRTUAL_CLASS = '#8b5cf6';
    private const COLOR_APPOINTMENT   = '#0ea5e9';

    public function __construct(private SchoolEventRepository $repo) {}

    public function index(): View
    {
        abort_unless(auth()->check(), 403);

        return view('teacher.calendar.index');
    }

    public function events(Request $request): JsonResponse
    {
        $user     = auth()->user();
        abort_unless($user !== null, 403);

        $schoolId = $this->activeSchoolId();
        $from     = $request->get('start', now()->startOfMonth()->toDateString());
        $to       = $request->get('end', now()->endOfMonth()->toDateString());
        // FullCalendar sends ISO datetimes; keep only the date part for ranges.
        $from     = substr($from, 0, 10);
        $to       = substr($to, 0, 10);

        $out = [];

        $this->addLessons($out, $user->id, $from, $to);
        $this->addExams($out, $user->id, $from, $to);
        $this->addAssignments($out, $user->id, $schoolId, $from, $to);
        $this->addVirtualClasses($out, $user->id, $schoolId, $from, $to);
        $this->addAppointments($out, $user->id, $schoolId, $from, $to);
        $this->addSchoolEvents($out, $user, $schoolId, $from, $to);

        return response()->json($out);
    }

    // ─── الحصص (recurring weekly lessons expanded onto real dates) ─────────────

    private function addLessons(array &$out, int $teacherId, string $from, string $to): void
    {
        $periods = SchedulePeriod::with(['subject', 'schedule.classRoom'])
            ->where('teacher_id', $teacherId)
            ->whereHas('schedule', fn ($q) => $q->active())
            ->get();

        if ($periods->isEmpty()) {
            return;
        }

        // SchedulePeriod::day_of_week is 0=Sunday..6=Saturday, matching Carbon's
        // dayOfWeek, so no remapping is needed.
        foreach (CarbonPeriod::create($from, $to) as $date) {
            $dow = $date->dayOfWeek;
            $day = $date->toDateString();

            foreach ($periods as $p) {
                if ($p->day_of_week !== $dow) {
                    continue;
                }

                // start_time/end_time are cast datetime:H:i (carry today's date);
                // take the time part only and graft it onto the iterated day.
                $start = $p->start_time ? $p->start_time->format('H:i:s') : '08:00:00';
                $end   = $p->end_time ? $p->end_time->format('H:i:s') : null;

                $subject   = optional($p->subject)->name ?? 'حصة';
                $className = optional(optional($p->schedule)->classRoom)->name;

                $out[] = [
                    'id'            => 'lesson-' . $p->id . '-' . $day,
                    'title'         => $className ? ($subject . ' — ' . $className) : $subject,
                    'start'         => $day . 'T' . $start,
                    'end'           => $end ? $day . 'T' . $end : null,
                    'allDay'        => false,
                    'color'         => self::COLOR_LESSON,
                    'url'           => route('teacher.schedule'),
                    'extendedProps' => ['event_type' => 'lesson', 'type_label' => 'حصة'],
                ];
            }
        }
    }

    // ─── الاختبارات ────────────────────────────────────────────────────────────

    private function addExams(array &$out, int $teacherId, string $from, string $to): void
    {
        $exams = Exam::where('teacher_id', $teacherId)
            ->whereNotNull('start_time')
            ->whereBetween('start_time', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($exams as $e) {
            $out[] = [
                'id'            => 'exam-' . $e->id,
                'title'         => 'اختبار: ' . $e->title,
                'start'         => $e->start_time->format('Y-m-d\TH:i:s'),
                'end'           => $e->end_time ? $e->end_time->format('Y-m-d\TH:i:s') : null,
                'allDay'        => false,
                'color'         => self::COLOR_EXAM,
                'url'           => route('teacher.exams.show', $e->id),
                'extendedProps' => ['event_type' => 'exam', 'type_label' => 'اختبار'],
            ];
        }
    }

    // ─── الواجبات (due date) ───────────────────────────────────────────────────

    private function addAssignments(array &$out, int $teacherId, ?int $schoolId, string $from, string $to): void
    {
        $assignments = Assignment::where('teacher_id', $teacherId)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereBetween('due_date', [$from, $to])
            ->get();

        foreach ($assignments as $a) {
            $day     = $a->due_date->toDateString();
            $time    = $a->due_time ? $a->due_time->format('H:i:s') : null;

            $out[] = [
                'id'            => 'assignment-' . $a->id,
                'title'         => 'واجب: ' . $a->title,
                'start'         => $time ? $day . 'T' . $time : $day,
                'end'           => null,
                'allDay'        => $time === null,
                'color'         => self::COLOR_ASSIGNMENT,
                'url'           => route('admin.assignments.show', $a->id),
                'extendedProps' => ['event_type' => 'assignment', 'type_label' => 'واجب'],
            ];
        }
    }

    // ─── الفصول الافتراضية ─────────────────────────────────────────────────────

    private function addVirtualClasses(array &$out, int $teacherId, ?int $schoolId, string $from, string $to): void
    {
        $classes = VirtualClass::where('teacher_id', $teacherId)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($classes as $vc) {
            $start = $vc->scheduled_at;
            $out[] = [
                'id'            => 'vc-' . $vc->id,
                'title'         => 'فصل افتراضي: ' . $vc->title,
                'start'         => $start->format('Y-m-d\TH:i:s'),
                'end'           => $vc->duration_minutes
                    ? $start->copy()->addMinutes($vc->duration_minutes)->format('Y-m-d\TH:i:s')
                    : null,
                'allDay'        => false,
                'color'         => self::COLOR_VIRTUAL_CLASS,
                'url'           => route('manage.virtual-classes.show', $vc->id),
                'extendedProps' => ['event_type' => 'virtual_class', 'type_label' => 'فصل افتراضي'],
            ];
        }
    }

    // ─── المواعيد ──────────────────────────────────────────────────────────────

    private function addAppointments(array &$out, int $teacherId, ?int $schoolId, string $from, string $to): void
    {
        $appointments = Appointment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where(fn ($q) => $q->where('target_user_id', $teacherId)->orWhere('student_id', $teacherId))
            ->whereBetween('appointment_date', [$from, $to])
            ->get();

        foreach ($appointments as $a) {
            $day  = $a->appointment_date->toDateString();
            $time = $a->appointment_time ?: '00:00:00';

            $out[] = [
                'id'            => 'appt-' . $a->id,
                'title'         => 'موعد',
                'start'         => $day . 'T' . $time,
                'end'           => null,
                'allDay'        => false,
                'color'         => self::COLOR_APPOINTMENT,
                'extendedProps' => ['event_type' => 'appointment', 'type_label' => 'موعد'],
            ];
        }
    }

    // ─── أحداث التقويم المدرسي (targeting enforced per-event) ──────────────────

    private function addSchoolEvents(array &$out, $user, ?int $schoolId, string $from, string $to): void
    {
        $events = $this->repo->forRange($schoolId, $from, $to)
            ->filter(fn ($e) => $e->isVisibleTo($user));

        foreach ($events as $e) {
            $out[] = [
                'id'            => 'event-' . $e->id,
                'title'         => $e->title,
                'start'         => $e->all_day
                    ? $e->start_date->toDateString()
                    : ($e->start_date->toDateString() . 'T' . ($e->start_time ?? '00:00:00')),
                'end'           => $e->end_date
                    ? ($e->all_day
                        ? $e->end_date->addDay()->toDateString()   // FullCalendar end is exclusive
                        : $e->end_date->toDateString() . 'T' . ($e->end_time ?? '23:59:59'))
                    : null,
                'allDay'        => (bool) $e->all_day,
                'color'         => $e->eventTypeColor(),
                'extendedProps' => [
                    'event_type' => $e->event_type,
                    'type_label' => $e->eventTypeLabel(),
                    'location'   => $e->location,
                ],
            ];
        }
    }
}
