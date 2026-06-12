<?php

namespace App\Modules\Appointments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Repositories\Contracts\AppointmentRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentBookingManagementController extends Controller
{
    use HasSchoolScope;

    public function __construct(private AppointmentRepository $appointments) {}

    // ─── Staff Booking List ──────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $isAdmin  = $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());

        $filters = [
            'status'    => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to'   => $request->get('date_to'),
            'q'         => trim((string) $request->get('q', '')),
        ];

        $bookings = $this->appointments->forStaff(
            $user->id,
            $schoolId,
            $isAdmin,
            $filters
        );

        $bookingStatuses = [
            'requested' => __('appointments.booking_status_requested'),
            'confirmed' => __('appointments.booking_status_confirmed'),
            'rejected'  => __('appointments.booking_status_rejected'),
            'cancelled' => __('appointments.booking_status_cancelled'),
            'completed' => __('appointments.booking_status_completed'),
        ];

        return view('appointments.bookings.manage.index', compact('bookings', 'filters', 'bookingStatuses', 'isAdmin'));
    }

    // ─── Booking Detail ──────────────────────────────────────────────────────

    public function show(int $id): View
    {
        $booking = $this->resolveAccessible($id);

        return view('appointments.bookings.manage.show', compact('booking'));
    }

    // ─── Decide: Confirm / Reject / Complete ─────────────────────────────────

    public function decide(Request $request, int $id): RedirectResponse
    {
        $booking = $this->resolveAccessible($id);
        $user    = auth()->user();
        $action  = $request->input('action'); // confirm | reject | complete

        if (! in_array($action, ['confirm', 'reject', 'complete'])) {
            abort(422);
        }

        $rules = [
            'action'        => ['required', 'in:confirm,reject,complete'],
            'decision_note' => $action === 'reject' ? ['required', 'string', 'max:1000'] : ['nullable', 'string', 'max:1000'],
        ];

        $data = $request->validate($rules);

        $statusMap = [
            'confirm'  => 'confirmed',
            'reject'   => 'rejected',
            'complete' => 'completed',
        ];

        $this->appointments->decide(
            $booking->id,
            $statusMap[$action],
            $user->id,
            $data['decision_note'] ?? null
        );

        $flashKey = match ($action) {
            'confirm'  => 'appointments.booking_flash_confirmed',
            'reject'   => 'appointments.booking_flash_rejected',
            'complete' => 'appointments.booking_flash_completed',
        };

        return redirect()
            ->route('manage.appointments.show', $booking->id)
            ->with('success', __($flashKey));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Find the booking and verify the current user is authorised to see/act on it.
     * Staff (target_user_id === me) or school admins may access.
     * Aborts 404 if not found; 403 if not authorised.
     */
    private function resolveAccessible(int $id)
    {
        $booking  = $this->appointments->find($id);
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $isAdmin  = $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());

        abort_if(! $booking, 404);

        // School scope check
        if ($schoolId && $booking->school_id !== $schoolId) {
            abort(403);
        }

        // Ownership check: admin can see all; teacher only their own
        if (! $isAdmin && $booking->target_user_id !== $user->id) {
            abort(403);
        }

        return $booking;
    }
}
