<?php

namespace App\Modules\Announcements\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Announcements\Actions\CreateAnnouncementAction;
use App\Modules\Announcements\Actions\UpdateAnnouncementAction;
use App\Modules\Announcements\DTOs\AnnouncementDto;
use App\Modules\Announcements\Http\Requests\AnnouncementRequest;
use App\Modules\Announcements\Repositories\Contracts\AnnouncementRepository;
use App\Modules\Announcements\Services\AnnouncementNotifier;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementRepository $announcements) {}

    /** school_id used for queries: null for super-admin (all schools). */
    protected function schoolId(): ?int
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? null : $user->school_id;
    }

    /** Enforce a write permission (backend, not UI). */
    protected function authorizeWrite(string $permission): void
    {
        if (!auth()->user()->canDo($permission)) {
            abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء');
        }
    }

    public function index(Request $request)
    {
        $schoolId = $this->schoolId();
        $filters = $request->only(['search', 'status', 'type', 'target_type']);

        $announcements = $this->announcements->paginate($schoolId, $filters, 20);

        return view('admin.announcements.index', compact('announcements', 'filters'));
    }

    public function create()
    {
        $this->authorizeWrite('announcements.create');
        $announcement = null;
        return view('admin.announcements.form', array_merge(
            ['announcement' => $announcement],
            $this->formData()
        ));
    }

    public function store(AnnouncementRequest $request, CreateAnnouncementAction $action)
    {
        $this->authorizeWrite('announcements.create');

        $publish = $request->input('action') === 'publish';
        if ($publish) {
            $this->authorizeWrite('announcements.publish');
        }

        $schoolId = $this->resolveSchoolIdForWrite($request);
        $status = $publish ? 'published' : 'draft';

        $dto = AnnouncementDto::fromArray($request->validated(), $schoolId, auth()->id(), $status);
        $action->execute($dto);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', $publish ? 'تم نشر الإعلان بنجاح.' : 'تم حفظ المسودة بنجاح.');
    }

    public function show(int $id)
    {
        $announcement = $this->findOrFail($id);
        return view('admin.announcements.show', compact('announcement'));
    }

    public function edit(int $id)
    {
        $this->authorizeWrite('announcements.edit');
        $announcement = $this->findOrFail($id);
        $announcement->load('targets');

        return view('admin.announcements.form', array_merge(
            ['announcement' => $announcement],
            $this->formData()
        ));
    }

    public function update(AnnouncementRequest $request, UpdateAnnouncementAction $action, int $id)
    {
        $this->authorizeWrite('announcements.edit');
        $announcement = $this->findOrFail($id);

        $publish = $request->input('action') === 'publish';
        if ($publish) {
            $this->authorizeWrite('announcements.publish');
        }

        $status = $publish ? 'published' : ($announcement->status === 'published' ? 'published' : 'draft');
        $schoolId = auth()->user()->isSuperAdmin()
            ? $announcement->school_id
            : auth()->user()->school_id;

        $dto = AnnouncementDto::fromArray($request->validated(), $schoolId, $announcement->created_by, $status);
        $action->execute($announcement, $dto);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'تم تحديث الإعلان بنجاح.');
    }

    public function destroy(int $id)
    {
        $this->authorizeWrite('announcements.delete');
        $announcement = $this->findOrFail($id);

        ActivityLog::logDelete($announcement, "حذف إعلان: {$announcement->title}");
        $this->announcements->delete($announcement);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'تم حذف الإعلان.');
    }

    public function activate(int $id, AnnouncementNotifier $notifier)
    {
        $this->authorizeWrite('announcements.publish');
        $announcement = $this->findOrFail($id);

        $wasPublished = $announcement->status === 'published';
        $announcement = $this->announcements->setStatus($announcement, 'published');
        ActivityLog::log('update', "تفعيل إعلان: {$announcement->title}", $announcement);

        if (!$wasPublished) {
            $notifier->dispatch($announcement);
        }

        return back()->with('success', 'تم تفعيل الإعلان.');
    }

    public function stop(int $id)
    {
        $this->authorizeWrite('announcements.publish');
        $announcement = $this->findOrFail($id);

        $this->announcements->setStatus($announcement, 'stopped');
        ActivityLog::log('update', "إيقاف إعلان: {$announcement->title}", $announcement);

        return back()->with('success', 'تم إيقاف الإعلان.');
    }

    public function duplicate(int $id)
    {
        $this->authorizeWrite('announcements.create');
        $announcement = $this->findOrFail($id);

        $copy = $this->announcements->duplicate($announcement);
        ActivityLog::logCreate($copy, "نسخ إعلان: {$copy->title}");

        return redirect()
            ->route('admin.announcements.edit', $copy->id)
            ->with('success', 'تم إنشاء نسخة كمسودة.');
    }

    public function readLog(int $id)
    {
        if (!auth()->user()->canDo('announcements.read_log')) {
            abort(403, 'غير مصرح لك بعرض سجل القراءة');
        }
        $announcement = $this->findOrFail($id);
        $log = $this->announcements->readLog($announcement);

        return view('admin.announcements.read-log', [
            'announcement' => $announcement,
            'read'         => $log['read'],
            'unread'       => $log['unread'],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Find honouring school scope; 404 when out of scope (blocks cross-school URLs). */
    protected function findOrFail(int $id): Announcement
    {
        $announcement = $this->announcements->find($id, $this->schoolId());
        if (!$announcement) {
            abort(404);
        }
        return $announcement;
    }

    protected function resolveSchoolIdForWrite(Request $request): int
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            $requested = (int) $request->input('school_id');
            return $requested ?: (int) (School::query()->value('id'));
        }
        return (int) $user->school_id;
    }

    protected function formData(): array
    {
        $user = auth()->user();
        $schoolId = $this->schoolId();

        $classesQuery = ClassRoom::query();
        $subjectsQuery = Subject::query();
        $usersQuery = User::query();
        $schools = collect();

        if ($schoolId !== null) {
            $classesQuery->whereHas('section', fn ($q) => $q->where('school_id', $schoolId));
            $subjectsQuery->where('school_id', $schoolId);
            $usersQuery->where('school_id', $schoolId);
        } else {
            $schools = School::orderBy('name')->get(['id', 'name']);
        }

        return [
            'classes'  => $classesQuery->orderBy('grade_level')->orderBy('name')->get(['id', 'name', 'grade_level']),
            'subjects' => $subjectsQuery->orderBy('name')->get(['id', 'name']),
            'roles'    => Role::orderBy('name')->get(['id', 'name', 'slug']),
            'users'    => $usersQuery->orderBy('name')->limit(500)->get(['id', 'name', 'school_id']),
            'schools'  => $schools,
            'gradeLevels' => range(1, 12),
        ];
    }
}
