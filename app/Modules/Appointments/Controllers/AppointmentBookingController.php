<?php

namespace App\Modules\Appointments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppointmentBookableRole;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Appointments\Repositories\Contracts\AppointmentRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentBookingController extends Controller
{
    use HasSchoolScope;

    public function __construct(private AppointmentRepository $appointments) {}

    // ─── My Bookings List ────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $user     = auth()->user();
        $asParent = $user?->isParent();

        $filters = [
            'status'    => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to'   => $request->get('date_to'),
        ];

        $bookings = $this->appointments->myBookings(
            $user->id,
            (bool) $asParent,
            $filters
        );

        $bookingStatuses = [
            'requested' => __('appointments.booking_status_requested'),
            'confirmed' => __('appointments.booking_status_confirmed'),
            'rejected'  => __('appointments.booking_status_rejected'),
            'cancelled' => __('appointments.booking_status_cancelled'),
            'completed' => __('appointments.booking_status_completed'),
        ];

        return view('appointments.bookings.my.index', compact('bookings', 'filters', 'bookingStatuses'));
    }

    // ─── Create Booking Form ─────────────────────────────────────────────────

    public function create(): View
    {
        $user     = auth()->user();
        $asParent = $user?->isParent();

        // If parent: load their children
        $children = collect();
        if ($asParent) {
            $children = $user->children()->select('users.id', 'users.name', 'users.school_id')->get();
        }

        // Determine school scope for loading bookable roles
        // For student: their own school; for parent: populated via JS after child selection
        $schoolId  = $asParent ? null : $this->activeSchoolId();
        $bookableRoles = $schoolId
            ? AppointmentBookableRole::forSchool($schoolId)->active()->ordered()->get()
            : collect();

        return view('appointments.bookings.my.create', compact('user', 'asParent', 'children', 'bookableRoles', 'schoolId'));
    }

    // ─── Store Booking ───────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Authoritative student + school: for a parent it is the VERIFIED child's
        // school (not the session scope); for a student, their own. This value is
        // non-null and drives every school-scoped rule below.
        [$studentId, $schoolId] = $this->resolveBookingContext($request);

        $data = $request->validate([
            'bookable_role_id'  => ['required', 'integer',
                Rule::exists('appointment_bookable_roles', 'id')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)->where('is_active', 1)),
            ],
            'target_user_id'    => ['required', 'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'subject_id'        => ['nullable', 'integer',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'reason'            => ['required', 'string', 'max:1000'],
            'appointment_date'  => ['required', 'date', 'after_or_equal:today'],
            'appointment_time'  => ['required', 'date_format:H:i'],
            'contact_method'    => ['required', 'in:in_person,call,virtual'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'attachment'        => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        // target_user_id must be a person actually reachable through the chosen
        // bookable role (prevents booking against an arbitrary same-school user).
        $role = AppointmentBookableRole::where('school_id', $schoolId)
            ->where('is_active', 1)
            ->find((int) $data['bookable_role_id']);
        $allowedIds = $role
            ? $this->peopleForRole($role, $schoolId, $studentId, isset($data['subject_id']) ? (int) $data['subject_id'] : null)
                ->pluck('id')->map(fn ($i) => (int) $i)->all()
            : [];
        if (! in_array((int) $data['target_user_id'], $allowedIds, true)) {
            return back()->withInput()->withErrors(['target_user_id' => __('appointments.booking_invalid_target')]);
        }

        // Handle attachment upload
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('appointments/attachments', 'public');
        }

        $this->appointments->create([
            'school_id'        => $schoolId,
            'student_id'       => $studentId,
            'booked_by'        => $user->id,
            'bookable_role_id' => $data['bookable_role_id'],
            'target_user_id'   => $data['target_user_id'],
            'subject_id'       => $data['subject_id'] ?? null,
            'reason'           => $data['reason'],
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'contact_method'   => $data['contact_method'],
            'notes'            => $data['notes'] ?? null,
            'attachment_path'  => $attachmentPath,
            'status'           => 'requested',
            'created_by'       => $user->id,
        ]);

        // TODO: notify target_user_id of the new booking request

        return redirect()
            ->route('my.appointments.index')
            ->with('success', __('appointments.booking_flash_created'));
    }

    // ─── Cancel Booking ──────────────────────────────────────────────────────

    public function cancel(int $id): RedirectResponse
    {
        $user    = auth()->user();
        $success = $this->appointments->cancel($id, $user->id);

        if (! $success) {
            return redirect()
                ->route('my.appointments.index')
                ->with('error', __('appointments.booking_flash_cancel_denied'));
        }

        return redirect()
            ->route('my.appointments.index')
            ->with('success', __('appointments.booking_flash_cancelled'));
    }

    // ─── AJAX: Resolve People for a Bookable Role ────────────────────────────

    public function people(Request $request): JsonResponse
    {
        // Authoritative student + school from the authenticated user (parent: the
        // VERIFIED child). The supplied bookable_role_id never decides the school.
        [$studentId, $schoolId] = $this->resolveBookingContext($request);

        $bookableRoleId = (int) $request->get('bookable_role_id');
        $subjectId      = (int) $request->get('subject_id') ?: null;

        // Reject any role that isn't an active bookable role of THIS school.
        $role = AppointmentBookableRole::where('school_id', $schoolId)
            ->where('is_active', 1)
            ->find($bookableRoleId);
        if (! $role) {
            return response()->json(['people' => [], 'subjects' => [], 'target_type' => null]);
        }

        $subjects = ($role->target_type === 'subject_teacher' && ! $subjectId)
            ? $this->subjectsForStudent($studentId, $schoolId)
            : collect();

        $people = $this->peopleForRole($role, $schoolId, $studentId, $subjectId);

        return response()->json([
            'people'      => $people->values()->all(),
            'subjects'    => $subjects->values()->all(),
            'target_type' => $role->target_type,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Resolve the authoritative [studentId, schoolId] for the current actor.
     * Parents may only book for one of their OWN children (verified via the
     * children relationship); the school is taken from that child, never the
     * session scope. Students book for themselves.
     *
     * @return array{0:int,1:int}
     */
    private function resolveBookingContext(Request $request): array
    {
        $user = auth()->user();

        if ($user?->isParent()) {
            $studentId = (int) $request->input('student_id');
            $child = $user->children()->where('users.id', $studentId)->first();
            abort_if(! $child, 403, __('appointments.access_denied'));

            return [(int) $child->id, (int) $child->school_id];
        }

        return [(int) $user->id, (int) $user->school_id];
    }

    /** Subjects the student is enrolled in (for the subject_teacher cascade), school-scoped. */
    private function subjectsForStudent(int $studentId, int $schoolId): \Illuminate\Support\Collection
    {
        // Sections the student belongs to (class_student → classes.section_id).
        $sectionIds = \Illuminate\Support\Facades\DB::table('class_student')
            ->join('classes', 'classes.id', '=', 'class_student.class_id')
            ->where('class_student.student_id', $studentId)
            ->pluck('classes.section_id')->filter()->unique()->all();

        // Subjects taught in those sections (via subject_teacher.section_id), school-scoped.
        return Subject::select('subjects.id', 'subjects.name')
            ->where('subjects.school_id', $schoolId)
            ->where('subjects.is_active', 1)
            ->whereExists(function ($q) use ($sectionIds) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('subject_teacher')
                    ->whereColumn('subject_teacher.subject_id', 'subjects.id')
                    ->when(! empty($sectionIds), fn ($s) => $s->whereIn('subject_teacher.section_id', $sectionIds));
            })
            ->orderBy('subjects.name')
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]);
    }

    /**
     * The set of people reachable through a bookable role, school-scoped.
     * Used both to render the person dropdown AND to validate the submitted
     * target_user_id (so a user can only be booked if the role actually exposes them).
     *
     * @return \Illuminate\Support\Collection<int,array{id:int,name:string}>
     */
    private function peopleForRole(AppointmentBookableRole $role, int $schoolId, ?int $studentId, ?int $subjectId): \Illuminate\Support\Collection
    {
        switch ($role->target_type) {
            case 'user':
                $u = $role->target_id
                    ? User::select('id', 'name')->where('id', $role->target_id)->where('school_id', $schoolId)->first()
                    : null;
                return $u ? collect([['id' => $u->id, 'name' => $u->name]]) : collect();

            case 'role':
                return User::select('users.id', 'users.name')
                    ->join('role_user', 'role_user.user_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->where('roles.slug', $role->target_id)
                    ->where('users.school_id', $schoolId)
                    ->orderBy('users.name')->get()
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

            case 'job_title':
                return User::select('id', 'name')
                    ->where('job_title_id', $role->target_id)
                    ->where('school_id', $schoolId)
                    ->orderBy('name')->get()
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

            case 'subject_teacher':
                if (! $subjectId) {
                    return collect();
                }
                return User::select('users.id', 'users.name')
                    ->join('subject_teacher', 'subject_teacher.user_id', '=', 'users.id')
                    ->where('subject_teacher.subject_id', $subjectId)
                    ->where('users.school_id', $schoolId)
                    ->orderBy('users.name')->distinct()->get()
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

            default:
                return collect();
        }
    }
}
