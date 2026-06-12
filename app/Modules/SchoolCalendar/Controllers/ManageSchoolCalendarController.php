<?php

namespace App\Modules\SchoolCalendar\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SchoolCalendar\Repositories\Contracts\SchoolEventRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageSchoolCalendarController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SchoolEventRepository $repo) {}

    // ─── Listing + Calendar ───────────────────────────────────────────────────

    public function index(): View
    {
        $schoolId = $this->activeSchoolId();
        $upcoming = $this->repo->forRange(
            $schoolId,
            now()->toDateString(),
            now()->addMonths(3)->toDateString()
        );

        return view('school-calendar.manage.index', compact('upcoming'));
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('school-calendar.manage.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEvent($request);

        $this->repo->create(array_merge($data, [
            'school_id'  => $this->activeSchoolId(),
            'created_by' => auth()->id(),
        ]));

        return redirect()
            ->route('manage.school-calendar.index')
            ->with('success', __('school_calendar.flash_created'));
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(int $id): View
    {
        $event = $this->resolveOwned($id);

        return view('school-calendar.manage.edit', compact('event'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $event = $this->resolveOwned($id);
        $data  = $this->validateEvent($request, $id);

        $this->repo->update($event->id, $data);

        return redirect()
            ->route('manage.school-calendar.index')
            ->with('success', __('school_calendar.flash_updated'));
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $this->resolveOwned($id);
        $this->repo->delete($id);

        return redirect()
            ->route('manage.school-calendar.index')
            ->with('success', __('school_calendar.flash_deleted'));
    }

    // ─── FullCalendar JSON feed ───────────────────────────────────────────────

    public function eventsJson(Request $request): JsonResponse
    {
        $schoolId = $this->activeSchoolId();
        $from     = $request->get('start', now()->startOfMonth()->toDateString());
        $to       = $request->get('end', now()->endOfMonth()->toDateString());

        $events = $this->repo->forRange($schoolId, $from, $to);

        return response()->json($events->map(fn ($e) => [
            'id'    => $e->id,
            'title' => $e->title,
            'start' => $e->all_day
                ? $e->start_date->toDateString()
                : ($e->start_date->toDateString() . 'T' . ($e->start_time ?? '00:00:00')),
            'end'   => $e->end_date
                ? ($e->all_day
                    ? $e->end_date->addDay()->toDateString()   // FullCalendar end is exclusive
                    : $e->end_date->toDateString() . 'T' . ($e->end_time ?? '23:59:59'))
                : null,
            'allDay'           => (bool) $e->all_day,
            'color'            => $e->eventTypeColor(),
            'extendedProps'    => [
                'event_type'   => $e->event_type,
                'type_label'   => $e->eventTypeLabel(),
                'location'     => $e->location,
                'description'  => $e->description,
                'edit_url'     => route('manage.school-calendar.edit', $e->id),
                'delete_url'   => route('manage.school-calendar.destroy', $e->id),
            ],
        ])->values());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveOwned(int $id)
    {
        $event    = $this->repo->findById($id);
        $schoolId = $this->activeSchoolId();

        if (! $event || $event->school_id !== $schoolId) {
            abort(403, __('school_calendar.access_denied'));
        }

        return $event;
    }

    private function validateEvent(Request $request, ?int $exceptId = null): array
    {
        $data = $request->validate([
            'title'       => 'required|string|max:160',
            'description' => 'nullable|string',
            'event_type'  => 'required|in:holiday,exam,activity,meeting,other',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'all_day'     => 'sometimes|boolean',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'color'       => 'nullable|string|max:20',
            'audience'    => 'nullable|array',
            'audience.*'  => 'in:all,students,parents,teachers,staff',
            'location'    => 'nullable|string|max:160',
        ]);

        $data['all_day'] = (bool) ($request->has('all_day') && $request->input('all_day'));

        if ($data['all_day']) {
            $data['start_time'] = null;
            $data['end_time']   = null;
        }

        if (empty($data['audience'])) {
            $data['audience'] = ['all'];
        }

        return $data;
    }
}
