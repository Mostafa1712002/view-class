<?php

namespace App\Modules\Discussion\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\DiscussionRoom;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Discussion\Repositories\Contracts\DiscussionRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Staff management of discussion rooms, topics, and comments.
 * Accessible to: super-admin, school-admin, teacher.
 */
class ManageDiscussionController extends Controller
{
    use HasSchoolScope;

    public function __construct(private DiscussionRepository $repo) {}

    // ── Rooms ────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $filters = ['status' => $request->get('status')];
        $rooms   = $this->repo->roomsForSchool($this->scopedSchoolId(), $filters);

        return view('discussion.manage.index', compact('rooms', 'filters'));
    }

    public function create(): View
    {
        return view('discussion.manage.create', array_merge(['room' => null], $this->formOptions()));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $data     = $this->validateRoom($request, $schoolId);
        $targets  = $this->extractTargeting($data);

        $room = $this->repo->createRoom(array_merge($data, [
            'school_id'         => $schoolId,
            'created_by'        => auth()->id(),
            'scope_type'        => 'school',
            'audience'          => ['all'],
            'allow_topics'      => $request->boolean('allow_topics', true),
            'allow_comments'    => $request->boolean('allow_comments', true),
            'requires_approval' => $request->boolean('requires_approval', false),
            'status'            => 'active',
        ]), $targets);

        ActivityLog::logCreate($room, 'إنشاء غرفة نقاش: '.$room->title);

        return redirect()
            ->route('manage.discussion-rooms.index')
            ->with('success', __('discussion.flash_room_created'));
    }

    public function edit(int $id): View
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        return view('discussion.manage.edit', array_merge(['room' => $room], $this->formOptions()));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        $data    = $this->validateRoom($request, $room->school_id);
        $targets = $this->extractTargeting($data);

        $old = $room->only(['title', 'description', 'instructions', 'category', 'subject_id', 'target_type', 'allow_topics', 'allow_comments', 'requires_approval']);

        $this->repo->updateRoom($id, array_merge($data, [
            'allow_topics'      => $request->boolean('allow_topics', $room->allow_topics),
            'allow_comments'    => $request->boolean('allow_comments', $room->allow_comments),
            'requires_approval' => $request->boolean('requires_approval', false),
        ]), $targets);

        ActivityLog::logUpdate($room->fresh(), 'تعديل غرفة نقاش: '.$room->title, $old);

        return redirect()
            ->route('manage.discussion-rooms.index')
            ->with('success', __('discussion.flash_room_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        ActivityLog::logDelete($room, 'حذف غرفة نقاش: '.$room->title);
        $this->repo->deleteRoom($id);

        return redirect()
            ->route('manage.discussion-rooms.index')
            ->with('success', __('discussion.flash_room_deleted'));
    }

    public function close(int $id): RedirectResponse
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        $this->repo->closeRoom($id);
        ActivityLog::logUpdate($room->fresh(), 'إيقاف غرفة نقاش: '.$room->title, ['status' => 'active']);

        return redirect()
            ->route('manage.discussion-rooms.index')
            ->with('success', __('discussion.flash_room_closed'));
    }

    public function reopen(int $id): RedirectResponse
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        $this->repo->reopenRoom($id);
        ActivityLog::logUpdate($room->fresh(), 'تفعيل غرفة نقاش: '.$room->title, ['status' => 'closed']);

        return redirect()
            ->route('manage.discussion-rooms.index')
            ->with('success', __('discussion.flash_room_reopened'));
    }

    public function toggleRoomComments(int $id): RedirectResponse
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        $room = $this->repo->toggleRoomComments($id);
        ActivityLog::logUpdate($room, ($room->allow_comments ? 'تفعيل' : 'إيقاف').' التعليقات في غرفة: '.$room->title, ['allow_comments' => ! $room->allow_comments]);

        return back()->with('success', $room->allow_comments
            ? __('discussion.flash_comments_enabled')
            : __('discussion.flash_comments_disabled'));
    }

    public function toggleRoomTopics(int $id): RedirectResponse
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        $room = $this->repo->toggleRoomTopics($id);
        ActivityLog::logUpdate($room, ($room->allow_topics ? 'تفعيل' : 'إيقاف').' الموضوعات الجديدة في غرفة: '.$room->title, ['allow_topics' => ! $room->allow_topics]);

        return back()->with('success', $room->allow_topics
            ? __('discussion.flash_topics_enabled')
            : __('discussion.flash_topics_disabled'));
    }

    public function report(int $id): View
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        $report = $this->repo->roomReport($id);

        return view('discussion.manage.report', compact('report'));
    }

    // ── Topics (staff actions) ────────────────────────────────────────────────

    public function pinTopic(int $topicId): RedirectResponse
    {
        $topic = $this->repo->findTopic($topicId);
        abort_if(! $topic, 404);
        $this->assertSchool($topic->school_id);

        $this->repo->pinTopic($topicId);

        return back()->with('success', __('discussion.flash_topic_pinned'));
    }

    public function closeTopic(int $topicId): RedirectResponse
    {
        $topic = $this->repo->findTopic($topicId);
        abort_if(! $topic, 404);
        $this->assertSchool($topic->school_id);

        $this->repo->closeTopic($topicId);

        return back()->with('success', __('discussion.flash_topic_closed'));
    }

    public function toggleTopicComments(int $topicId): RedirectResponse
    {
        $topic = $this->repo->findTopic($topicId);
        abort_if(! $topic, 404);
        $this->assertSchool($topic->school_id);

        $topic = $this->repo->toggleTopicComments($topicId);
        ActivityLog::logUpdate($topic, ($topic->comments_closed ? 'إيقاف' : 'تفعيل').' التعليق على موضوع: '.$topic->title, ['comments_closed' => ! $topic->comments_closed]);

        return back()->with('success', $topic->comments_closed
            ? __('discussion.flash_topic_comments_disabled')
            : __('discussion.flash_topic_comments_enabled'));
    }

    public function hideTopic(int $topicId): RedirectResponse
    {
        $topic = $this->repo->findTopic($topicId);
        abort_if(! $topic, 404);
        $this->assertSchool($topic->school_id);

        $topic = $this->repo->toggleTopicHidden($topicId);
        ActivityLog::logUpdate($topic, ($topic->is_hidden ? 'إخفاء' : 'إظهار').' موضوع: '.$topic->title, ['is_hidden' => ! $topic->is_hidden]);

        return back()->with('success', $topic->is_hidden
            ? __('discussion.flash_topic_hidden')
            : __('discussion.flash_topic_shown'));
    }

    public function deleteTopic(int $topicId): RedirectResponse
    {
        $topic = $this->repo->findTopic($topicId);
        abort_if(! $topic, 404);
        $this->assertSchool($topic->school_id);

        $roomId = $topic->room_id;
        ActivityLog::logDelete($topic, 'حذف موضوع نقاش: '.$topic->title);
        $this->repo->deleteTopic($topicId);

        return redirect()
            ->route('discussion.room', $roomId)
            ->with('success', __('discussion.flash_topic_deleted'));
    }

    public function deleteComment(int $commentId): RedirectResponse
    {
        $comment = $this->repo->findComment($commentId);
        abort_if(! $comment, 404);
        $this->assertSchool($comment->school_id);

        $topicId = $comment->topic_id;
        ActivityLog::logDelete($comment, 'حذف رد في غرفة نقاش');
        $this->repo->deleteComment($commentId);

        return redirect()
            ->route('discussion.topic', $topicId)
            ->with('success', __('discussion.flash_comment_deleted'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Validate the room create/update payload (shared by store + update).
     * Targeting rules mirror virtual-classes (#234).
     *
     * @return array<string,mixed>
     */
    private function validateRoom(Request $request, ?int $schoolId): array
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:160'],
            'description'  => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'category'     => ['nullable', 'string', 'max:100'],
            'subject_id'   => ['nullable', 'integer', Rule::exists('subjects', 'id')->where(fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q)],

            // Targeting (mirrors announcements / virtual-classes).
            'target_type'       => ['required', Rule::in(DiscussionRoom::TARGET_TYPES)],
            'grade_levels'      => ['nullable', 'array'],
            'grade_levels.*'    => ['integer'],
            'class_ids'         => ['nullable', 'array'],
            'class_ids.*'       => ['integer'],
            'user_target_ids'   => ['nullable', 'array'],
            'user_target_ids.*' => ['integer'],
            'role_target_ids'   => ['nullable', 'array'],
            'role_target_ids.*' => ['integer'],
            'job_title_ids'     => ['nullable', 'array'],
            'job_title_ids.*'   => ['integer'],
        ]);

        // A "specific" audience with no picks would target nobody — reject it so
        // the room can't silently vanish from every account.
        $requireOne = [
            'specific_users' => 'user_target_ids',
            'specific_roles' => 'role_target_ids',
            'job_titles'     => 'job_title_ids',
        ];
        $tt = $validated['target_type'] ?? 'all';
        if (isset($requireOne[$tt]) && empty($validated[$requireOne[$tt]])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $requireOne[$tt] => __('discussion.target_pick_required'),
            ]);
        }

        return $validated;
    }

    /**
     * Split the targeting fields off the model attributes:
     *  - normalises grade/class narrowing (only meaningful for `students`),
     *  - returns the user/role/job_title pivot arrays for the repository.
     *
     * Casts grade/class values to int — checkbox values arrive as strings and
     * JSON_CONTAINS (used by scopeVisibleTo) is type-strict, so "7" never matches
     * the int 7.
     *
     * @return array{user:array<int>,role:array<int>,job_title:array<int>}
     */
    private function extractTargeting(array &$data): array
    {
        $tt = $data['target_type'] ?? 'all';

        $userIds = $data['user_target_ids'] ?? [];
        $roleIds = $data['role_target_ids'] ?? [];
        $jobIds  = $data['job_title_ids']   ?? [];
        unset($data['user_target_ids'], $data['role_target_ids'], $data['job_title_ids']);

        if ($tt === 'students') {
            $data['grade_levels'] = ! empty($data['grade_levels']) ? array_map('intval', array_values($data['grade_levels'])) : null;
            $data['class_ids']    = ! empty($data['class_ids']) ? array_map('intval', array_values($data['class_ids'])) : null;
        } else {
            $data['grade_levels'] = null;
            $data['class_ids']    = null;
        }

        return [
            'user'      => $tt === 'specific_users' ? $userIds : [],
            'role'      => $tt === 'specific_roles' ? $roleIds : [],
            'job_title' => $tt === 'job_titles'     ? $jobIds  : [],
        ];
    }

    /**
     * Option lists for the create/edit form (subject + audience-selector data),
     * scoped to the active school. Mirrors VirtualClassController::formOptions.
     *
     * @return array<string,mixed>
     */
    private function formOptions(): array
    {
        $schoolId = $this->activeSchoolId();

        $classes = ClassRoom::query()
            ->when($schoolId, function ($q) use ($schoolId) {
                $yearIds = AcademicYear::where('school_id', $schoolId)->pluck('id');
                $q->whereIn('academic_year_id', $yearIds);
            })
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level']);

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name', 'school_id']);

        $roles = Role::orderBy('name')->get(['id', 'name', 'slug']);

        $jobTitles = JobTitle::query()
            ->active()
            ->forSchool($schoolId)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'school_id']);

        $users = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'school_id']);

        $gradeLevels = range(1, 12);

        return compact('classes', 'subjects', 'roles', 'jobTitles', 'users', 'gradeLevels');
    }

    /**
     * Abort with 403 if the resource does not belong to the active school.
     */
    private function assertSchool(int $resourceSchoolId): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }
        abort_if($this->activeSchoolId() === null || $resourceSchoolId !== $this->activeSchoolId(), 403);
    }
}
