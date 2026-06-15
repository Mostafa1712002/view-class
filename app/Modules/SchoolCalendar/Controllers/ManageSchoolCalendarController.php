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

    /** Gate an action on a calendar permission; abort 403 when denied. */
    private function authorizeCalendar(string $permission): void
    {
        if (! auth()->check() || ! auth()->user()->canDo($permission)) {
            abort(403, __('school_calendar.access_denied'));
        }
    }

    // ─── Listing + Calendar ───────────────────────────────────────────────────

    public function index(): View
    {
        $this->authorizeCalendar('calendar.view');
        $schoolId = $this->scopedSchoolId();
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
        $this->authorizeCalendar('calendar.create_event');
        return view('school-calendar.manage.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCalendar('calendar.create_event');
        $data = $this->validateEvent($request);

        $event = $this->repo->create(array_merge($data, [
            'school_id'  => $this->activeSchoolId(),
            'created_by' => auth()->id(),
        ]));

        \App\Models\ActivityLog::logCreate($event, 'إنشاء حدث في التقويم المدرسي: ' . $event->title);

        return redirect()
            ->route('manage.school-calendar.index')
            ->with('success', __('school_calendar.flash_created'));
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(int $id): View
    {
        $this->authorizeCalendar('calendar.edit_event');
        $event = $this->resolveOwned($id);

        return view('school-calendar.manage.edit', compact('event'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeCalendar('calendar.edit_event');
        $event = $this->resolveOwned($id);
        $old   = $event->only(array_keys($event->getAttributes()));
        $data  = $this->validateEvent($request, $id);

        $this->repo->update($event->id, $data);

        \App\Models\ActivityLog::logUpdate($event->fresh(), 'تعديل حدث في التقويم المدرسي: ' . $event->title, $old);

        return redirect()
            ->route('manage.school-calendar.index')
            ->with('success', __('school_calendar.flash_updated'));
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $this->authorizeCalendar('calendar.delete_event');
        $event = $this->resolveOwned($id);
        $this->repo->delete($id);

        \App\Models\ActivityLog::logDelete($event, 'حذف حدث من التقويم المدرسي: ' . $event->title);

        return redirect()
            ->route('manage.school-calendar.index')
            ->with('success', __('school_calendar.flash_deleted'));
    }

    // ─── FullCalendar JSON feed ───────────────────────────────────────────────

    public function eventsJson(Request $request): JsonResponse
    {
        $this->authorizeCalendar('calendar.view');
        $schoolId = $this->scopedSchoolId();
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

    // ─── Print (PDF: daily / weekly / monthly) ────────────────────────────────

    public function print(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeCalendar('calendar.print');

        $view = in_array($request->get('view'), ['day', 'week', 'month'], true)
            ? $request->get('view')
            : 'month';

        $anchor = $request->get('date')
            ? \Illuminate\Support\Carbon::parse($request->get('date'))
            : now();

        [$from, $to, $rangeLabel] = match ($view) {
            'day'   => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay(), $anchor->translatedFormat('d F Y')],
            'week'  => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek(), $anchor->copy()->startOfWeek()->translatedFormat('d F') . ' - ' . $anchor->copy()->endOfWeek()->translatedFormat('d F Y')],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth(), $anchor->translatedFormat('F Y')],
        };

        $schoolId = $this->scopedSchoolId();
        $events   = $this->repo->forRange($schoolId, $from->toDateString(), $to->toDateString());

        $schoolName = $schoolId
            ? optional(\App\Models\School::find($schoolId))->name
            : null;

        $html = view('school-calendar.manage.print', [
            'events'      => $events,
            'view'        => $view,
            'rangeLabel'  => $rangeLabel,
            'pdf_title'   => __('school_calendar.print_title') . ' — ' . __('school_calendar.view_' . $view),
            'pdf_school'  => $schoolName ?? '',
            'pdf_date'    => now()->format('Y-m-d H:i'),
        ])->render();

        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'orientation'      => $view === 'month' ? 'L' : 'P',
            'default_font'     => 'xbriyaz',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'tempDir'          => $tmp,
            'margin_top'       => 12,
            'margin_bottom'    => 14,
            'margin_left'      => 10,
            'margin_right'     => 10,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:8px;color:#94a3b8;font-family:dejavusans;">صفحة {PAGENO} من {nb}</div>'
        );
        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output('school-calendar.pdf', \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="school-calendar.pdf"',
            ]
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveOwned(int $id)
    {
        $event    = $this->repo->findById($id);
        abort_if(! $event, 404);

        $me = auth()->user();
        if (! ($me?->isSuperAdmin() ?? false)) {
            $schoolId = $this->scopedSchoolId();
            abort_if($schoolId === null || $event->school_id !== $schoolId, 403, __('school_calendar.access_denied'));
        }

        return $event;
    }

    private function validateEvent(Request $request, ?int $exceptId = null): array
    {
        $data = $request->validate([
            'title'       => 'required|string|max:160',
            'description' => 'nullable|string',
            'event_type'  => 'required|in:' . implode(',', \App\Models\SchoolEvent::TYPES),
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'all_day'     => 'sometimes|boolean',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'color'       => 'nullable|string|max:20',
            'audience'    => 'nullable|array',
            'audience.*'  => 'in:all,students,parents,teachers,staff',
            'location'    => 'nullable|string|max:160',
        ], [
            'end_date.after_or_equal' => __('school_calendar.err_end_before_start'),
        ]);

        $data['all_day'] = (bool) ($request->has('all_day') && $request->input('all_day'));

        if ($data['all_day']) {
            $data['start_time'] = null;
            $data['end_time']   = null;
        }

        // End time may not precede start time on a same-day, timed event.
        if (! $data['all_day']
            && ! empty($data['start_time']) && ! empty($data['end_time'])
            && (empty($data['end_date']) || $data['end_date'] === $data['start_date'])
            && $data['end_time'] < $data['start_time']
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'end_time' => __('school_calendar.err_end_time_before_start'),
            ]);
        }

        if (empty($data['audience'])) {
            $data['audience'] = ['all'];
        }

        return $data;
    }
}
