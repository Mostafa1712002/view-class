<?php

namespace App\Modules\SchoolCalendar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SchoolCalendar\Repositories\Contracts\SchoolEventRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyCalendarController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SchoolEventRepository $repo) {}

    private function authorizeCalendar(string $permission): void
    {
        if (! auth()->check() || ! auth()->user()->canDo($permission)) {
            abort(403, __('school_calendar.access_denied'));
        }
    }

    public function index(): View
    {
        $this->authorizeCalendar('calendar.view');
        return view('school-calendar.view.index');
    }

    public function eventsJson(Request $request): JsonResponse
    {
        $this->authorizeCalendar('calendar.view');
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $from     = $request->get('start', now()->startOfMonth()->toDateString());
        $to       = $request->get('end', now()->endOfMonth()->toDateString());

        // Map user role → audience key used in events
        $audienceKey = match (true) {
            $user && $user->isStudent()    => 'students',
            $user && $user->isParent()     => 'parents',
            $user && $user->isTeacher()    => 'teachers',
            // school-admin and super-admin see all events
            default                        => null,
        };

        $events = $this->repo->forRange($schoolId, $from, $to, $audienceKey);

        $out = $events->map(fn ($e) => [
            'id'    => $e->id,
            'title' => $e->title,
            'start' => $e->all_day
                ? $e->start_date->toDateString()
                : ($e->start_date->toDateString() . 'T' . ($e->start_time ?? '00:00:00')),
            'end'   => $e->end_date
                ? ($e->all_day
                    ? $e->end_date->addDay()->toDateString()
                    : $e->end_date->toDateString() . 'T' . ($e->end_time ?? '23:59:59'))
                : null,
            'allDay'        => (bool) $e->all_day,
            'color'         => $e->eventTypeColor(),
            'extendedProps' => [
                'event_type'  => $e->event_type,
                'type_label'  => $e->eventTypeLabel(),
                'location'    => $e->location,
                'description' => $e->description,
            ],
        ])->values()->all();

        // #180: the current user's appointments appear on their calendar.
        $appointments = \App\Models\Appointment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where(fn ($q) => $q->where('student_id', $user->id)->orWhere('target_user_id', $user->id))
            ->whereBetween('appointment_date', [$from, $to])
            ->get();
        foreach ($appointments as $a) {
            $out[] = [
                'id'            => 'appt-' . $a->id,
                'title'         => 'موعد',
                'start'         => $a->appointment_date->toDateString() . 'T' . ($a->appointment_time ?: '00:00:00'),
                'end'           => null,
                'allDay'        => false,
                'color'         => '#0ea5e9',
                'extendedProps' => ['event_type' => 'appointment', 'type_label' => 'موعد', 'description' => (string) $a->status],
            ];
        }

        // #180: virtual classes the user is involved with appear on their calendar.
        $vcQuery = \App\Models\VirtualClass::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('status', '!=', 'cancelled');
        if ($user && $user->isStudent()) {
            $vcQuery->whereIn('class_id', $user->enrolledClassIds() ?: [0]);
        } elseif ($user && $user->isTeacher()) {
            $vcQuery->where('teacher_id', $user->id);
        }
        foreach ($vcQuery->get() as $vc) {
            $start = $vc->scheduled_at;
            $out[] = [
                'id'            => 'vc-' . $vc->id,
                'title'         => $vc->title,
                'start'         => $start->format('Y-m-d\TH:i:s'),
                'end'           => $vc->duration_minutes ? $start->copy()->addMinutes($vc->duration_minutes)->format('Y-m-d\TH:i:s') : null,
                'allDay'        => false,
                'color'         => '#8b5cf6',
                'extendedProps' => ['event_type' => 'virtual_class', 'type_label' => 'فصل افتراضي', 'description' => (string) $vc->description],
            ];
        }

        return response()->json($out);
    }
}
