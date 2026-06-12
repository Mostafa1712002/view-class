<?php

namespace App\Modules\VirtualClasses\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Validation\Rule;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use App\Services\ZoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff management of virtual classroom sessions.
 * Accessible to: super-admin, school-admin, teacher.
 */
class VirtualClassController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private VirtualClassRepositoryInterface $repo,
        private ZoomService $zoom,
    ) {}

    // ── Listing ───────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $isAdmin  = $user->isSuperAdmin() || $user->isSchoolAdmin();

        $filters = [
            'status' => $request->get('status'),
        ];

        $classes = $this->repo->forStaff($user->id, $schoolId, $isAdmin);

        return view('virtual-classes.manage.index', compact('classes', 'filters'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('virtual-classes.manage.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:160'],
            'description'      => ['nullable', 'string'],
            // teacher_id must belong to the actor's active school (no cross-tenant assignment)
            'teacher_id'       => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q)],
            'scheduled_at'     => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'audience'         => ['nullable', 'array'],
        ]);

        $zoomData = null;

        // Attempt Zoom meeting creation — failure is non-fatal
        try {
            $zoomData = $this->zoom->createMeeting([
                'title'            => $data['title'],
                'start_time'       => \Carbon\Carbon::parse($data['scheduled_at'])->toIso8601ZuluString(),
                'duration_minutes' => (int) $data['duration_minutes'],
                'description'      => $data['description'] ?? '',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('VirtualClassController: Zoom meeting creation failed', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->repo->create(array_merge($data, [
            'school_id'      => $schoolId,
            'created_by'     => auth()->id(),
            'status'         => 'scheduled',
            'audience'       => $data['audience'] ?? ['all'],
            'zoom_meeting_id' => $zoomData['id']        ?? null,
            'join_url'       => $zoomData['join_url']   ?? null,
            'start_url'      => $zoomData['start_url']  ?? null,
            'passcode'       => $zoomData['passcode']   ?? null,
        ]));

        if ($zoomData) {
            return redirect()
                ->route('manage.virtual-classes.index')
                ->with('success', __('virtual_classes.flash_created'));
        }

        return redirect()
            ->route('manage.virtual-classes.index')
            ->with('warning', __('virtual_classes.flash_created_no_zoom'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(int $id): View
    {
        $vc = $this->resolveOwned($id);

        return view('virtual-classes.manage.show', compact('vc'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(int $id): View
    {
        $vc = $this->resolveOwned($id);

        return view('virtual-classes.manage.edit', compact('vc'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $vc = $this->resolveOwned($id);
        $schoolId = $this->activeSchoolId();

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:160'],
            'description'      => ['nullable', 'string'],
            'teacher_id'       => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q)],
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'audience'         => ['nullable', 'array'],
        ]);

        $data['audience'] = $data['audience'] ?? ['all'];

        // Only admins may reassign the session to a different teacher; a teacher
        // editing their own session cannot hand it to someone else.
        $actor = auth()->user();
        if (! ($actor->isSuperAdmin() || $actor->isSchoolAdmin())) {
            $data['teacher_id'] = $vc->teacher_id;
        }

        $this->repo->update($vc->id, $data);

        return redirect()
            ->route('manage.virtual-classes.index')
            ->with('success', __('virtual_classes.flash_updated'));
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function cancel(int $id): RedirectResponse
    {
        $vc = $this->resolveOwned($id);

        $this->repo->updateStatus($vc->id, 'cancelled');

        return redirect()
            ->route('manage.virtual-classes.index')
            ->with('success', __('virtual_classes.flash_cancelled'));
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $this->resolveOwned($id);
        $this->repo->delete($id);

        return redirect()
            ->route('manage.virtual-classes.index')
            ->with('success', __('virtual_classes.flash_deleted'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveOwned(int $id)
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();

        $vc = $this->repo->find($id, $schoolId);
        abort_if(! $vc, 404);

        // Teachers may only manage their own sessions
        if (! $user->isSuperAdmin() && ! $user->isSchoolAdmin()) {
            abort_if($vc->teacher_id !== $user->id, 403);
        }

        return $vc;
    }
}
