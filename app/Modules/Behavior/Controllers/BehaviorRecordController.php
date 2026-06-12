<?php

namespace App\Modules\Behavior\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Behavior;
use App\Models\BehaviorAction;
use App\Models\BehaviorGroup;
use App\Models\BehaviorRecord;
use App\Models\Notification;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BehaviorRecordController extends Controller
{
    use HasSchoolScope;

    private function tab(Request $request): string
    {
        $tab = (string) $request->get('tab', 'student');

        return in_array($tab, BehaviorGroup::SCOPES, true) ? $tab : 'student';
    }

    private function usersFor(string $tab)
    {
        $schoolId = $this->activeSchoolId();
        $role = $tab === 'teacher' ? 'teacher' : 'student';

        // A plain teacher may only record behaviour for students they actually
        // teach (#192: "لا يسجل المعلم سلوك لطالب لا يدرسه"). Admins/supervisors see all.
        $restrictIds = $this->restrictStudentIds($tab);

        return User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', $role))
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->when($restrictIds !== null, fn ($w) => $w->whereIn('id', $restrictIds))
            ->orderBy('name')
            ->limit(1000)
            ->get(['id', 'name']);
    }

    /**
     * When the actor is a plain teacher recording *student* behaviour, return the
     * set of student IDs they are allowed to record for. Returns null when no
     * restriction applies (admin/supervisor/super-admin, or the teacher tab).
     *
     * @return array<int>|null
     */
    private function restrictStudentIds(string $tab): ?array
    {
        if ($tab !== 'student') {
            return null;
        }

        $actor = auth()->user();
        $isPlainTeacher = $actor && $actor->isTeacher() && ! $actor->isSchoolAdmin() && ! $actor->isSuperAdmin();
        if (! $isPlainTeacher) {
            return null;
        }

        return $this->teachingStudentIds((int) $actor->id, $this->activeSchoolId());
    }

    /**
     * Resolve the student IDs a teacher teaches, unioning the three places the
     * teacher⇄class link is recorded: classes they lead, classes on their
     * timetable (schedule_periods → schedules.class_id), and classes whose
     * section they teach (subject_teacher.section_id). Students are matched on
     * both enrolment sources (class_student pivot + users.class_room_id).
     *
     * @return array<int>
     */
    private function teachingStudentIds(int $teacherId, ?int $schoolId): array
    {
        $classIds = collect();

        // 1) classes they lead
        $classIds = $classIds->merge(
            DB::table('classes')->where('lead_teacher_id', $teacherId)->pluck('id')
        );

        // 2) classes on their timetable
        $classIds = $classIds->merge(
            DB::table('schedule_periods')
                ->join('schedules', 'schedules.id', '=', 'schedule_periods.schedule_id')
                ->where('schedule_periods.teacher_id', $teacherId)
                ->pluck('schedules.class_id')
        );

        // 3) classes whose section they are assigned to teach
        $sectionIds = DB::table('subject_teacher')
            ->where('user_id', $teacherId)
            ->whereNotNull('section_id')
            ->pluck('section_id');
        if ($sectionIds->isNotEmpty()) {
            $classIds = $classIds->merge(
                DB::table('classes')->whereIn('section_id', $sectionIds)->pluck('id')
            );
        }

        $classIds = $classIds->filter()->unique()->values();
        if ($classIds->isEmpty()) {
            return [];
        }

        $studentIds = DB::table('users')
            ->where(function ($w) use ($classIds) {
                $w->whereIn('class_room_id', $classIds)
                    ->orWhereIn('id', DB::table('class_student')->whereIn('class_id', $classIds)->select('student_id'));
            })
            ->whereNull('deleted_at')
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->pluck('id');

        return $studentIds->map(fn ($id) => (int) $id)->all();
    }

    private function behaviorsFor(string $tab)
    {
        $schoolId = $this->activeSchoolId();

        return Behavior::query()
            ->where('is_active', true)
            ->whereHas('group', fn ($g) => $g->where('scope', $tab))
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function index(Request $request): View
    {
        $tab = $this->tab($request);
        $schoolId = $this->activeSchoolId();
        $q = trim((string) $request->get('q', ''));

        // A teacher (not admin) sees only the records they recorded — not the whole
        // school's disciplinary log (#192).
        $actor = auth()->user();
        $teacherOnly = $actor && $actor->isTeacher() && ! $actor->isSchoolAdmin() && ! $actor->isSuperAdmin();

        $records = BehaviorRecord::query()
            ->with(['subject', 'behavior', 'action', 'recorder'])
            ->where('scope', $tab)
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->when($teacherOnly, fn ($w) => $w->where('recorded_by', $actor->id))
            ->when($q !== '', fn ($w) => $w->whereHas('subject', fn ($s) => $s->where('name', 'like', '%'.$q.'%')))
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('admin.behavior.records.index', compact('records', 'tab', 'q'));
    }

    public function create(Request $request): View
    {
        $tab = $this->tab($request);

        // When opened from a specific student's page (?student=ID), lock the select to
        // that student instead of listing everyone (card #127).
        $lockedUser = null;
        if ($request->filled('student')) {
            $schoolId = $this->activeSchoolId();
            $role = $tab === 'teacher' ? 'teacher' : 'student';
            $restrictIds = $this->restrictStudentIds($tab);
            $lockedUser = User::query()
                ->whereKey((int) $request->get('student'))
                ->whereHas('roles', fn ($r) => $r->where('slug', $role))
                ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
                ->when($restrictIds !== null, fn ($w) => $w->whereIn('id', $restrictIds))
                ->first(['id', 'name']);
        }

        return view('admin.behavior.records.create', [
            'tab' => $tab,
            'users' => $lockedUser ? collect([$lockedUser]) : $this->usersFor($tab),
            'behaviors' => $this->behaviorsFor($tab),
            'lockedUser' => $lockedUser,
        ]);
    }

    /** AJAX: active actions for a behaviour (card #115 apply flow). */
    public function actions(Request $request): JsonResponse
    {
        $behaviorId = (int) $request->get('behavior_id');
        if (! $behaviorId) {
            return response()->json(['actions' => []]);
        }

        $schoolId = $this->activeSchoolId();

        // Ensure the behaviour is in scope before exposing its actions.
        $behavior = Behavior::query()
            ->where('is_active', true)
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->find($behaviorId);

        if (! $behavior) {
            return response()->json(['actions' => []]);
        }

        $actions = BehaviorAction::query()
            ->where('behavior_id', $behaviorId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get(['id', 'description', 'points', 'point_type', 'notify_parent', 'needs_followup']);

        return response()->json([
            'actions' => $actions->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'signed_points' => $a->signedPoints(),
                'notify_parent' => (bool) $a->notify_parent,
                'needs_followup' => (bool) $a->needs_followup,
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tab = $this->tab($request);
        $schoolId = $this->activeSchoolId();
        $role = $tab === 'teacher' ? 'teacher' : 'student';

        $data = $request->validate([
            'subject_user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($q) use ($schoolId) {
                    if ($schoolId) {
                        $q->where('school_id', $schoolId);
                    }
                }),
            ],
            'behavior_id' => [
                'required',
                Rule::exists('behaviors', 'id')->where(function ($q) use ($schoolId) {
                    $q->where('is_active', true)->whereNull('deleted_at');
                    if ($schoolId) {
                        $q->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id'));
                    }
                }),
            ],
            'behavior_action_id' => ['nullable', Rule::exists('behavior_actions', 'id')->where(fn ($q) => $q->where('is_active', true)->whereNull('deleted_at'))],
            'points' => ['nullable', 'integer', 'between:-100000,100000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        // The subject must actually hold the role for this tab, and behave within scope.
        $subject = User::query()->whereKey($data['subject_user_id'])
            ->whereHas('roles', fn ($r) => $r->where('slug', $role))->firstOrFail();

        // A plain teacher may only record for students they teach (#192).
        $restrictIds = $this->restrictStudentIds($tab);
        abort_if($restrictIds !== null && ! in_array((int) $subject->id, $restrictIds, true), 403);

        $behavior = Behavior::query()->with('group')->findOrFail($data['behavior_id']);
        abort_unless(optional($behavior->group)->scope === $tab, 422);

        $action = null;
        if (! empty($data['behavior_action_id'])) {
            $action = BehaviorAction::query()->where('behavior_id', $behavior->id)->find($data['behavior_action_id']);
            abort_unless($action, 422); // action must belong to the chosen behaviour
        }

        // Points: explicit override wins, else the action's signed value, else 0.
        $points = array_key_exists('points', $data) && $data['points'] !== null
            ? (int) $data['points']
            : ($action ? $action->signedPoints() : 0);

        $notifyParent = $tab === 'student' && $action && $action->notify_parent;

        $record = BehaviorRecord::create([
            'school_id' => $schoolId,
            'scope' => $tab,
            'subject_user_id' => $subject->id,
            'behavior_id' => $behavior->id,
            'behavior_action_id' => $action?->id,
            'points' => $points,
            'note' => $data['note'] ?? null,
            'needs_followup' => $action?->needs_followup ?? false,
            'notified_parent' => false,
            'recorded_by' => auth()->id(),
        ]);

        if ($notifyParent) {
            $this->notifyParents($subject, $behavior, $record);
            $record->update(['notified_parent' => true]);
        }

        // If recording started from a student's page, return there (card #131).
        $fromStudent = (int) $request->input('from_student_id');
        if ($tab === 'student' && $fromStudent && $fromStudent === (int) $subject->id) {
            return redirect()->route('admin.users.students.behavior', $subject->id)
                ->with('status', __('behavior.flash.record_created'));
        }

        return redirect()->route('admin.behavior.records.index', ['tab' => $tab])
            ->with('status', __('behavior.flash.record_created'));
    }

    /** Notify the student's parents (only those allowed to receive notifications). */
    private function notifyParents(User $student, Behavior $behavior, BehaviorRecord $record): void
    {
        $parentIds = DB::table('parent_student')
            ->where('student_id', $student->id)
            ->where('can_receive_notifications', true)
            ->pluck('parent_id');

        foreach ($parentIds as $pid) {
            Notification::create([
                'user_id' => $pid,
                'type' => 'system',
                'title' => __('behavior.notify.title'),
                'body' => __('behavior.notify.body', ['student' => $student->name, 'behavior' => $behavior->name]),
                'icon' => 'la la-balance-scale',
                'color' => $record->points < 0 ? 'danger' : 'success',
                'data' => ['behavior_record_id' => $record->id],
            ]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $record = BehaviorRecord::query()
            ->when($schoolId, fn ($w) => $w->where(fn ($x) => $x->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->whereKey($id)->firstOrFail();
        $scope = $record->scope;
        $record->delete();

        return redirect()->route('admin.behavior.records.index', ['tab' => $scope])
            ->with('status', __('behavior.flash.record_deleted'));
    }
}
