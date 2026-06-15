<?php

namespace App\Modules\VirtualClasses\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\VirtualClasses\Actions\RecalcAttendanceAction;
use App\Modules\VirtualClasses\Actions\StartVirtualClassAction;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use App\Services\ZoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Staff management of virtual classroom sessions.
 * Accessible to: super-admin, school-admin, teacher.
 *
 * Authorisation is enforced two ways: routes carry the `permission:` middleware,
 * and each mutating action re-checks `canDo()` so the controller fails closed even
 * if a route is reached without the gate.
 */
class VirtualClassController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private VirtualClassRepositoryInterface $repo,
        private ZoomService $zoom,
    ) {}

    // ── Listing (tabs) ──────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $user     = auth()->user();
        $schoolId = $this->scopedSchoolId();
        $isAdmin  = $user->isSuperAdmin() || $user->isSchoolAdmin();

        $tab = in_array($request->get('tab'), ['today', 'recorded', 'old', 'all'], true)
            ? $request->get('tab')
            : 'all';

        $classes = $this->repo->forStaff($user->id, $schoolId, $isAdmin, $tab);

        return view('virtual-classes.manage.index', compact('classes', 'tab'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $this->authorizeAction('virtual_classes.create');

        return view('virtual-classes.manage.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAction('virtual_classes.create');

        $schoolId = $this->activeSchoolId();
        $data     = $this->validateSession($request, $schoolId);

        $zoomData = null;

        if (($data['platform'] ?? 'zoom') === 'zoom') {
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
        }

        $vc = $this->repo->create(array_merge($data, [
            'school_id'       => $schoolId,
            'created_by'      => auth()->id(),
            'status'          => 'scheduled',
            'audience'        => $data['audience'] ?? ['all'],
            'zoom_meeting_id' => $zoomData['id']        ?? null,
            'join_url'        => $zoomData['join_url']   ?? null,
            'start_url'       => $zoomData['start_url']  ?? null,
            'passcode'        => $zoomData['passcode']   ?? null,
        ]));

        ActivityLog::logCreate($vc, "إنشاء فصل افتراضي: {$vc->title}");

        if (($data['platform'] ?? 'zoom') === 'zoom' && ! $zoomData) {
            return redirect()
                ->route('manage.virtual-classes.index')
                ->with('warning', __('virtual_classes.flash_created_no_zoom'));
        }

        return redirect()
            ->route('manage.virtual-classes.index')
            ->with('success', __('virtual_classes.flash_created'));
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
        $this->authorizeAction('virtual_classes.edit');
        $vc = $this->resolveOwned($id);

        return view('virtual-classes.manage.edit', array_merge(['vc' => $vc], $this->formOptions()));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAction('virtual_classes.edit');
        $vc       = $this->resolveOwned($id);
        $schoolId = $this->activeSchoolId();
        $old      = $vc->toArray();

        $data = $this->validateSession($request, $schoolId, ['after:now' => false]);
        $data['audience'] = $data['audience'] ?? ['all'];

        // Only admins may reassign the session to another teacher.
        $actor = auth()->user();
        if (! ($actor->isSuperAdmin() || $actor->isSchoolAdmin())) {
            $data['teacher_id'] = $vc->teacher_id;
        }

        $this->repo->update($vc->id, $data);
        ActivityLog::logUpdate($vc->fresh(), "تعديل فصل افتراضي: {$vc->title}", $old);

        return redirect()
            ->route('manage.virtual-classes.index')
            ->with('success', __('virtual_classes.flash_updated'));
    }

    // ── Start (teacher/host) ─────────────────────────────────────────────────

    public function start(int $id, StartVirtualClassAction $action): RedirectResponse
    {
        $this->authorizeAction('virtual_classes.start');
        $vc = $this->resolveOwned($id);

        abort_unless($vc->isJoinable() || $vc->status === 'live', 422, __('virtual_classes.join_not_yet'));

        $result = $action->execute($vc);

        if (! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return redirect()
            ->route('manage.virtual-classes.show', $vc->id)
            ->with('warning', __('virtual_classes.zoom_not_linked'));
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function cancel(int $id): RedirectResponse
    {
        $this->authorizeAction('virtual_classes.edit');
        $vc = $this->resolveOwned($id);

        $this->repo->updateStatus($vc->id, 'cancelled');
        ActivityLog::log('cancel_virtual_class', "إلغاء فصل افتراضي: {$vc->title}", $vc);

        return redirect()
            ->route('manage.virtual-classes.index')
            ->with('success', __('virtual_classes.flash_cancelled'));
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $this->authorizeAction('virtual_classes.delete');
        $vc = $this->resolveOwned($id);

        ActivityLog::logDelete($vc, "حذف فصل افتراضي: {$vc->title}");
        $this->repo->delete($id);

        return redirect()
            ->route('manage.virtual-classes.index')
            ->with('success', __('virtual_classes.flash_deleted'));
    }

    // ── Attendance: view / recalc / export / clear cache ─────────────────────

    public function attendance(int $id): View
    {
        $this->authorizeAction('virtual_classes.view_attendance');
        $vc = $this->resolveOwned($id);

        $attendees = $this->repo->attendeesFor($vc->id);
        $roster    = $this->repo->rosterStudentIds($vc->class_id);
        $summary   = Cache::get("vc_attendance_summary_{$vc->id}");

        return view('virtual-classes.manage.attendance', compact('vc', 'attendees', 'roster', 'summary'));
    }

    public function recalcAttendance(int $id, RecalcAttendanceAction $action): RedirectResponse
    {
        $this->authorizeAction('virtual_classes.recalc_attendance');
        $vc = $this->resolveOwned($id);

        $summary = $action->execute($vc);

        return redirect()
            ->route('manage.virtual-classes.attendance', $vc->id)
            ->with('success', __('virtual_classes.flash_recalc', [
                'present' => $summary['present'],
                'absent'  => $summary['absent'],
            ]));
    }

    public function exportAttendance(int $id): StreamedResponse
    {
        $this->authorizeAction('virtual_classes.view_attendance');
        $vc        = $this->resolveOwned($id);
        $attendees = $this->repo->attendeesFor($vc->id);

        $filename = 'virtual-class-' . $vc->id . '-attendance.csv';
        $isRtl    = app()->getLocale() === 'ar';

        return response()->streamDownload(function () use ($attendees, $vc, $isRtl) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Arabic correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('virtual_classes.att_student'),
                __('virtual_classes.att_joined'),
                __('virtual_classes.att_left'),
                __('virtual_classes.att_duration'),
                __('virtual_classes.att_status'),
            ]);
            foreach ($attendees as $a) {
                $name = $isRtl && optional($a->student)->name_ar ? $a->student->name_ar : optional($a->student)->name;
                fputcsv($out, [
                    $name,
                    optional($a->joined_at)->format('Y-m-d H:i'),
                    optional($a->left_at)->format('Y-m-d H:i'),
                    $a->duration_minutes,
                    $a->attendance_status ? __('virtual_classes.att_' . $a->attendance_status) : '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function clearCache(int $id): RedirectResponse
    {
        $this->authorizeAction('virtual_classes.clear_cache');
        $vc = $this->resolveOwned($id);

        Cache::forget("vc_attendance_summary_{$vc->id}");
        ActivityLog::log('clear_cache_virtual_class', "حذف كاش الفصل الافتراضي: {$vc->title}", $vc);

        return redirect()
            ->route('manage.virtual-classes.attendance', $vc->id)
            ->with('success', __('virtual_classes.flash_cache_cleared'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeAction(string $slug): void
    {
        abort_unless(auth()->user()->canDo($slug), 403);
    }

    /**
     * @return array{title:string,...}
     */
    private function validateSession(Request $request, ?int $schoolId, array $opts = []): array
    {
        $scheduledRules = ['required', 'date'];
        if (($opts['after:now'] ?? true) !== false) {
            $scheduledRules[] = 'after:now';
        }

        return $request->validate([
            'title'            => ['required', 'string', 'max:160'],
            'description'      => ['nullable', 'string'],
            'teacher_id'       => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q)],
            'class_id'         => ['nullable', 'integer'],
            'subject_id'       => ['nullable', 'integer', Rule::exists('subjects', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q)],
            'scheduled_at'     => $scheduledRules,
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'platform'         => ['required', Rule::in(['zoom', 'teams', 'external', 'internal'])],
            'external_url'     => ['nullable', 'url', 'max:1000', 'required_if:platform,external', 'required_if:platform,teams'],
            'audience'         => ['nullable', 'array'],
        ]);
    }

    /**
     * Class + subject option lists scoped to the active school.
     */
    private function formOptions(): array
    {
        $schoolId = $this->activeSchoolId();

        $classes = ClassRoom::query()
            ->when($schoolId, function ($q) use ($schoolId) {
                $yearIds = AcademicYear::where('school_id', $schoolId)->pluck('id');
                $q->whereIn('academic_year_id', $yearIds);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return compact('classes', 'subjects');
    }

    private function resolveOwned(int $id)
    {
        $user     = auth()->user();
        $schoolId = $this->scopedSchoolId();

        $vc = $this->repo->find($id, $schoolId);
        abort_if(! $vc, 404);

        // Teachers may only manage their own sessions.
        if (! $user->isSuperAdmin() && ! $user->isSchoolAdmin()) {
            abort_if($vc->teacher_id !== $user->id, 403);
        }

        return $vc;
    }
}
