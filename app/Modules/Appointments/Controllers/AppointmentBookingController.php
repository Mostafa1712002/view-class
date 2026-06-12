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
        $user     = auth()->user();
        $asParent = $user?->isParent();
        $schoolId = $this->activeSchoolId();

        // For parents, the student_id comes from the form; for students it is themselves
        $validatedStudentId = $asParent ? null : $user->id;

        $rules = [
            'bookable_role_id'  => ['required', 'integer',
                Rule::exists('appointment_bookable_roles', 'id')
                    ->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId)->where('is_active', 1) : $q->where('is_active', 1)),
            ],
            'target_user_id'    => ['required', 'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q),
            ],
            'subject_id'        => ['nullable', 'integer',
                Rule::exists('subjects', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q),
            ],
            'reason'            => ['required', 'string', 'max:1000'],
            'appointment_date'  => ['required', 'date', 'after_or_equal:today'],
            'appointment_time'  => ['required', 'date_format:H:i'],
            'contact_method'    => ['required', 'in:in_person,call,virtual'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'attachment'        => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];

        if ($asParent) {
            $rules['student_id'] = ['required', 'integer',
                Rule::exists('parent_student', 'student_id')
                    ->where(fn ($q) => $q->where('parent_id', $user->id)),
            ];
        }

        $data = $request->validate($rules);

        $studentId = $asParent ? (int) $data['student_id'] : $user->id;

        // Determine school_id: for student, their own; for parent, child's school
        if ($asParent) {
            $child    = User::find($studentId);
            $schoolId = $child?->school_id ?? $schoolId;
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
        $bookableRoleId = (int) $request->get('bookable_role_id');
        $subjectId      = (int) $request->get('subject_id');
        $studentId      = (int) $request->get('student_id');

        if (! $bookableRoleId) {
            return response()->json(['people' => [], 'subjects' => []]);
        }

        $role = AppointmentBookableRole::where('is_active', 1)->find($bookableRoleId);
        if (! $role) {
            return response()->json(['people' => [], 'subjects' => []]);
        }

        $schoolId = $role->school_id;
        $people   = collect();
        $subjects = collect();

        switch ($role->target_type) {
            case 'user':
                // One specific user configured
                if ($role->target_id) {
                    $u = User::select('id', 'name')
                        ->where('id', $role->target_id)
                        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                        ->first();
                    if ($u) {
                        $people = collect([['id' => $u->id, 'name' => $u->name]]);
                    }
                }
                break;

            case 'role':
                // All users with the given role slug in the school
                $people = User::select('users.id', 'users.name')
                    ->join('role_user', 'role_user.user_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->where('roles.slug', $role->target_id)
                    ->when($schoolId, fn ($q) => $q->where('users.school_id', $schoolId))
                    ->orderBy('users.name')
                    ->get()
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
                break;

            case 'job_title':
                // All users with the given job_title_id in the school
                $people = User::select('id', 'name')
                    ->where('job_title_id', $role->target_id)
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
                break;

            case 'subject_teacher':
                // Step 1: Get the student's enrolled subjects (scoped to their class + school)
                // Step 2: If a subject_id is given, get teachers of that subject in the school
                if (! $subjectId && $studentId) {
                    // Return the list of subjects for the student to choose from
                    $student = User::find($studentId);
                    if ($student) {
                        $classIds = $student->enrolledClassIds();
                        $subjects = Subject::select('id', 'name')
                            ->where('school_id', $schoolId)
                            ->where('is_active', 1)
                            ->whereExists(function ($q) use ($classIds) {
                                $q->select(\DB::raw(1))
                                  ->from('subject_teacher')
                                  ->whereColumn('subject_teacher.subject_id', 'subjects.id');
                            })
                            ->orderBy('name')
                            ->get()
                            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]);
                    }
                } elseif ($subjectId) {
                    // Return teachers for the selected subject
                    $people = User::select('users.id', 'users.name')
                        ->join('subject_teacher', 'subject_teacher.user_id', '=', 'users.id')
                        ->where('subject_teacher.subject_id', $subjectId)
                        ->when($schoolId, fn ($q) => $q->where('users.school_id', $schoolId))
                        ->orderBy('users.name')
                        ->distinct()
                        ->get()
                        ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
                }
                break;
        }

        return response()->json([
            'people'      => $people->values()->all(),
            'subjects'    => $subjects->values()->all(),
            'target_type' => $role->target_type,
        ]);
    }
}
