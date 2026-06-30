<?php

namespace App\Modules\Discussion\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DiscussionRoom;
use App\Modules\Discussion\Repositories\Contracts\DiscussionRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Member-facing discussion views (all authenticated users).
 */
class DiscussionController extends Controller
{
    use HasSchoolScope;

    public function __construct(private DiscussionRepository $repo) {}

    /**
     * List of active rooms visible to this user's school.
     */
    public function index(): View
    {
        $user = auth()->user();

        // Members see only rooms whose targeting includes them. Staff are users
        // too — a teacher/admin sees rooms targeted at their role/bucket — which
        // is exactly what roomsVisibleTo() returns.
        $rooms = $this->repo->roomsVisibleTo(
            $user,
            $this->activeSchoolId(),
            ['status' => 'active']
        );

        return view('discussion.member.index', compact('rooms'));
    }

    /**
     * List topics inside a room.
     */
    public function room(int $roomId): View
    {
        $room = $this->repo->findRoom($roomId);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);
        abort_if($room->status !== 'active', 403);

        // Targeting gate: a member may only open a room that targets them. Staff
        // (super-admin/school-admin/teacher) manage every room, so they bypass —
        // same lockstep rule that drives the member listing.
        abort_if(! $this->isStaff() && ! $this->repo->isRoomVisibleTo($room, auth()->user()), 403);

        // Staff see hidden topics; members do not.
        $topics = $this->repo->topicsForRoom($roomId, $this->activeSchoolId(), 20, $this->isStaff());

        return view('discussion.member.room', compact('room', 'topics'));
    }

    /**
     * Show create topic form.
     */
    public function topicCreate(int $roomId): View
    {
        $room = $this->repo->findRoom($roomId);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);
        abort_if($room->status !== 'active', 403);
        // Room must allow new topics (staff may always post).
        abort_if(! $room->allow_topics && ! $this->isStaff(), 403, __('discussion.topics_disabled_notice'));

        return view('discussion.member.topic_create', compact('room'));
    }

    /**
     * Store a new topic.
     */
    public function topicStore(Request $request, int $roomId): RedirectResponse
    {
        $room = $this->repo->findRoom($roomId);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);
        abort_if($room->status !== 'active', 403);
        abort_if(! $room->allow_topics && ! $this->isStaff(), 403, __('discussion.topics_disabled_notice'));

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body'  => ['required', 'string'],
        ]);

        $topic = $this->repo->createTopic([
            'room_id'    => $room->id,
            'school_id'  => $this->activeSchoolId(),
            'title'      => $data['title'],
            'body'       => $data['body'],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('discussion.topic', $topic->id)
            ->with('success', __('discussion.flash_topic_created'));
    }

    /**
     * Show a topic with its comments.
     */
    public function topicShow(int $topicId): View
    {
        $topic = $this->repo->findTopic($topicId);
        abort_if(! $topic, 404);
        $this->assertSchool($topic->school_id);

        // Ensure the parent room is accessible
        abort_if(! $topic->room || $topic->room->status !== 'active', 403);
        // Members cannot open a hidden topic; staff can.
        abort_if($topic->is_hidden && ! $this->isStaff(), 404);

        $comments = $this->repo->commentsForTopic($topicId);

        // Whether replies are allowed: room flag + topic flag + topic not closed.
        $commentsAllowed = $topic->room->allow_comments
            && ! $topic->comments_closed
            && ! $topic->is_closed;

        return view('discussion.member.topic', compact('topic', 'comments', 'commentsAllowed'));
    }

    /**
     * Store a comment (reply) on a topic.
     */
    public function commentStore(Request $request, int $topicId): RedirectResponse
    {
        $topic = $this->repo->findTopic($topicId);
        abort_if(! $topic, 404);
        $this->assertSchool($topic->school_id);
        abort_if(! $topic->room || $topic->room->status !== 'active', 403);
        abort_if($topic->is_hidden && ! $this->isStaff(), 404);
        abort_if($topic->is_closed, 403);
        // Comments must be enabled at the room AND topic level (toggle_comments).
        abort_if(! $topic->room->allow_comments, 403, __('discussion.comments_disabled_notice'));
        abort_if($topic->comments_closed, 403, __('discussion.topic_comments_disabled_notice'));

        $data = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $this->repo->addComment([
            'topic_id'  => $topic->id,
            'school_id' => $this->activeSchoolId(),
            'user_id'   => auth()->id(),
            'body'      => $data['body'],
        ]);

        return redirect()
            ->route('discussion.topic', $topicId)
            ->with('success', __('discussion.flash_comment_added'));
    }

    /**
     * Delete own comment (member self-service).
     */
    public function commentDestroy(int $commentId): RedirectResponse
    {
        $comment = $this->repo->findComment($commentId);
        abort_if(! $comment, 404);
        $this->assertSchool($comment->school_id);

        // Only the author may delete their own comment here
        abort_if($comment->user_id !== auth()->id(), 403);

        $topicId = $comment->topic_id;
        $this->repo->deleteComment($commentId);

        return redirect()
            ->route('discussion.topic', $topicId)
            ->with('success', __('discussion.flash_comment_deleted'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assertSchool(int $resourceSchoolId): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }
        abort_if($resourceSchoolId !== $this->activeSchoolId(), 403);
    }

    private function isStaff(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isSchoolAdmin() || $user->isTeacher());
    }
}
