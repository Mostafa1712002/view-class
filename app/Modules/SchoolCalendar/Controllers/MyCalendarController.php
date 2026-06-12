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

    public function index(): View
    {
        return view('school-calendar.view.index');
    }

    public function eventsJson(Request $request): JsonResponse
    {
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

        return response()->json($events->map(fn ($e) => [
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
        ])->values());
    }
}
