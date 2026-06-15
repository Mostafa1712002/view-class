<?php

namespace App\Modules\Discussion\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\Discussion\Repositories\Contracts\DiscussionRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('discussion.manage.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:160'],
            'description'  => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'scope_type'   => ['nullable', 'string', 'max:20'],
            'audience'     => ['nullable', 'array'],
        ]);

        $room = $this->repo->createRoom([
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'instructions'      => $data['instructions'] ?? null,
            'school_id'         => $this->activeSchoolId(),
            'created_by'        => auth()->id(),
            'scope_type'        => $data['scope_type'] ?? 'school',
            'audience'          => $data['audience'] ?? ['all'],
            'allow_topics'      => $request->boolean('allow_topics', true),
            'allow_comments'    => $request->boolean('allow_comments', true),
            'requires_approval' => $request->boolean('requires_approval', false),
            'status'            => 'active',
        ]);

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

        return view('discussion.manage.edit', compact('room'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

        $data = $request->validate([
            'title'        => ['required', 'string', 'max:160'],
            'description'  => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
        ]);

        $old = $room->only(['title', 'description', 'instructions', 'allow_topics', 'allow_comments', 'requires_approval']);

        $this->repo->updateRoom($id, array_merge($data, [
            'allow_topics'      => $request->boolean('allow_topics', $room->allow_topics),
            'allow_comments'    => $request->boolean('allow_comments', $room->allow_comments),
            'requires_approval' => $request->boolean('requires_approval', false),
        ]));

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
