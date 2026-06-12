<?php

namespace App\Modules\Appointments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Appointments\Repositories\Contracts\AppointmentScheduleRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentScheduleController extends Controller
{
    use HasSchoolScope;

    public function __construct(private AppointmentScheduleRepository $schedules) {}

    public function index(Request $request): View
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $ownerId  = ($user && $user->isTeacher() && ! $user->isSchoolAdmin() && ! $user->isSuperAdmin())
            ? $user->id
            : null;

        $filters = [
            'q'         => trim((string) $request->get('q', '')),
            'status'    => $request->get('status'),
            'mode'      => $request->get('mode'),
            'date_from' => $request->get('date_from'),
            'date_to'   => $request->get('date_to'),
        ];

        $schedules = $this->schedules->paginate($schoolId, $ownerId, $filters);

        return view('appointments.schedules.index', compact('schedules', 'filters'));
    }

    public function create(): View
    {
        return view('appointments.schedules.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSchedule($request);

        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $ownerId  = ($user && $user->isTeacher() && ! $user->isSchoolAdmin() && ! $user->isSuperAdmin())
            ? $user->id
            : ($data['owner_id'] ?? $user?->id);

        $this->schedules->create(array_merge($data, [
            'school_id'  => $schoolId,
            'owner_id'   => $ownerId,
            'created_by' => $user?->id,
            'days'       => $data['days'] ?? [],
        ]));

        return redirect()
            ->route('manage.appointment-schedules.index')
            ->with('success', __('appointments.flash_created'));
    }

    public function edit(int $id): View
    {
        $schedule = $this->resolveOwned($id);

        return view('appointments.schedules.edit', compact('schedule'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schedule = $this->resolveOwned($id);
        $data     = $this->validateSchedule($request);

        $this->schedules->update($schedule, array_merge($data, [
            'days' => $data['days'] ?? [],
        ]));

        return redirect()
            ->route('manage.appointment-schedules.index')
            ->with('success', __('appointments.flash_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schedule = $this->resolveOwned($id);
        $this->schedules->delete($schedule);

        return redirect()
            ->route('manage.appointment-schedules.index')
            ->with('success', __('appointments.flash_deleted'));
    }

    public function toggle(int $id): RedirectResponse
    {
        $schedule = $this->resolveOwned($id);
        $updated  = $this->schedules->toggleBookingOpen($schedule);

        $msg = $updated->booking_open
            ? __('appointments.flash_booking_opened')
            : __('appointments.flash_booking_closed');

        return redirect()
            ->route('manage.appointment-schedules.index')
            ->with('success', $msg);
    }

    public function copy(int $id): RedirectResponse
    {
        $schedule = $this->resolveOwned($id);
        $copy     = $this->schedules->copy($schedule, auth()->id());

        return redirect()
            ->route('manage.appointment-schedules.edit', $copy->id)
            ->with('success', __('appointments.flash_copied'));
    }

    public function show(int $id): View
    {
        $schedule = $this->resolveOwned($id);

        return view('appointments.schedules.show', compact('schedule'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Find a schedule, scoping by school + owner if the current user is a teacher.
     * Aborts 404 if not found; 403 if found but owned by another person and user is not admin.
     */
    private function resolveOwned(int $id)
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $isAdmin  = $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());
        $ownerId  = $isAdmin ? null : ($user?->id);

        $schedule = $this->schedules->findScoped($id, $schoolId, $ownerId);
        abort_if(! $schedule, 404);

        return $schedule;
    }

    private function validateSchedule(Request $request): array
    {
        return $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'date_from'        => ['required', 'date'],
            'date_to'          => ['required', 'date', 'after_or_equal:date_from'],
            'days'             => ['nullable', 'array'],
            'days.*'           => ['string', 'in:sun,mon,tue,wed,thu,fri,sat'],
            'time_from'        => ['required', 'date_format:H:i'],
            'time_to'          => ['required', 'date_format:H:i', 'after:time_from'],
            'slot_minutes'     => ['required', 'integer', 'min:5', 'max:480'],
            'max_appointments' => ['nullable', 'integer', 'min:1'],
            'location'         => ['nullable', 'string', 'max:255'],
            'mode'             => ['required', 'in:in_person,call,virtual'],
            'status'           => ['required', 'in:active,inactive'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'booking_open'     => ['nullable', 'boolean'],
            'owner_id'         => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }
}
