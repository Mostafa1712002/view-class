<?php

namespace App\Modules\SchoolCalendar\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\SchoolEvent;
use App\Models\User;
use App\Modules\SchoolCalendar\Repositories\Contracts\SchoolEventRepository;
use App\Modules\SchoolCalendar\Services\SchoolEventNotifier;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageSchoolCalendarController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private SchoolEventRepository $repo,
        private SchoolEventNotifier $notifier,
    ) {}

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
        $event = null;

        return view('school-calendar.manage.create', array_merge(
            compact('event'),
            $this->formData()
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCalendar('calendar.create_event');
        [$data, $userTargetIds] = $this->validateEvent($request);

        $event = $this->repo->create(array_merge($data, [
            'school_id'  => $this->resolveSchoolIdForWrite($request),
            'created_by' => auth()->id(),
        ]));

        $this->repo->syncTargets($event, $userTargetIds);

        if ($event->notify) {
            $this->notifier->notifyCreated($event->fresh('targets'));
        }

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
        $event->load('targets');

        return view('school-calendar.manage.edit', array_merge(
            compact('event'),
            $this->formData()
        ));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeCalendar('calendar.edit_event');
        $event = $this->resolveOwned($id);
        $old   = $event->only(array_keys($event->getAttributes()));
        [$data, $userTargetIds] = $this->validateEvent($request, $id);

        $this->repo->update($event->id, $data);
        $this->repo->syncTargets($event, $userTargetIds);

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

    /**
     * Validate and shape the event payload.
     *
     * @return array{0: array<string,mixed>, 1: int[]} [model columns, user target ids]
     */
    private function validateEvent(Request $request, ?int $exceptId = null): array
    {
        $v = $request->validate([
            'title'            => 'required|string|max:160',
            'description'      => 'nullable|string',
            'event_type'       => 'required|in:' . implode(',', SchoolEvent::TYPES),
            'start_date'       => 'required|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'all_day'          => 'sometimes|boolean',
            'start_time'       => 'nullable|date_format:H:i',
            'end_time'         => 'nullable|date_format:H:i',
            'color'            => 'nullable|string|max:20',
            'audience'         => 'nullable|array',
            'audience.*'       => 'in:all,students,parents,teachers,staff',
            'location'         => 'nullable|string|max:160',
            // Targeting
            'target_type'      => 'nullable|in:school,specific',
            'grade_levels'     => 'nullable|array',
            'grade_levels.*'   => 'integer',
            'class_ids'        => 'nullable|array',
            'class_ids.*'      => 'integer',
            'user_target_ids'  => 'nullable|array',
            'user_target_ids.*' => 'integer',
            // Notification
            'notify'           => 'sometimes|boolean',
            'remind_before'    => 'sometimes|boolean',
            'remind_minutes'   => 'nullable|integer|min:5|max:10080',
        ], [
            'end_date.after_or_equal' => __('school_calendar.err_end_before_start'),
        ]);

        $allDay = (bool) ($request->has('all_day') && $request->input('all_day'));
        $startTime = $allDay ? null : ($v['start_time'] ?? null);
        $endTime   = $allDay ? null : ($v['end_time'] ?? null);

        // End time may not precede start time on a same-day, timed event.
        if (! $allDay && ! empty($startTime) && ! empty($endTime)
            && (empty($v['end_date']) || $v['end_date'] === $v['start_date'])
            && $endTime < $startTime
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'end_time' => __('school_calendar.err_end_time_before_start'),
            ]);
        }

        $targetType = $v['target_type'] ?? SchoolEvent::TARGET_SCHOOL;
        $specific   = $targetType === SchoolEvent::TARGET_SPECIFIC;

        $audience       = $v['audience'] ?? [];
        $gradeLevels    = $specific ? array_map('intval', $v['grade_levels'] ?? []) : [];
        $classIds       = $specific ? array_map('intval', $v['class_ids'] ?? []) : [];
        $userTargetIds  = $specific ? array_map('intval', $v['user_target_ids'] ?? []) : [];

        if ($specific && empty($gradeLevels) && empty($classIds) && empty($userTargetIds)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user_target_ids' => __('school_calendar.err_specific_required'),
            ]);
        }

        if (! $specific && empty($audience)) {
            $audience = ['all'];
        }

        $remindBefore = (bool) ($request->has('remind_before') && $request->input('remind_before'));

        $model = [
            'title'          => $v['title'],
            'description'    => $v['description'] ?? null,
            'event_type'     => $v['event_type'],
            'start_date'     => $v['start_date'],
            'end_date'       => $v['end_date'] ?? null,
            'all_day'        => $allDay,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'color'          => $v['color'] ?? null,
            'location'       => $v['location'] ?? null,
            'target_type'    => $targetType,
            'audience'       => $specific ? null : $audience,
            'grade_levels'   => $gradeLevels ?: null,
            'class_ids'      => $classIds ?: null,
            'notify'         => (bool) ($request->has('notify') && $request->input('notify')),
            'remind_before'  => $remindBefore,
            'remind_minutes' => $remindBefore ? ($v['remind_minutes'] ?? 60) : null,
        ];

        return [$model, $userTargetIds];
    }

    /** Resolve the owning school for a write (super-admin may pick one). */
    private function resolveSchoolIdForWrite(Request $request): int
    {
        $scoped = $this->activeSchoolId();
        if ($scoped !== null) {
            return $scoped;
        }
        // super-admin with "all schools" active: take the chosen school, else first.
        $requested = (int) $request->input('school_id');

        return $requested ?: (int) School::query()->value('id');
    }

    /** Lookup data the create/edit form needs for targeting. */
    private function formData(): array
    {
        $user     = auth()->user();
        $schoolId = $user->isSuperAdmin() ? null : $user->school_id;

        $classesQuery = ClassRoom::query()
            ->leftJoin('sections', 'sections.id', '=', 'classes.section_id');
        $usersQuery = User::query();
        $schools    = collect();

        if ($schoolId !== null) {
            $classesQuery->where('sections.school_id', $schoolId);
            $usersQuery->where('school_id', $schoolId);
        } else {
            $schools = School::orderBy('name')->get(['id', 'name']);
        }

        return [
            'classes' => $classesQuery
                ->orderBy('classes.grade_level')
                ->orderBy('classes.name')
                ->get(['classes.id', 'classes.name', 'classes.grade_level', 'sections.school_id as school_id']),
            'users'       => $usersQuery->orderBy('name')->limit(500)->get(['id', 'name', 'school_id']),
            'schools'     => $schools,
            'gradeLevels' => range(1, 12),
        ];
    }
}
