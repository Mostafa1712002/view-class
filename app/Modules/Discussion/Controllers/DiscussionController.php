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
        $rooms = $this->repo->roomsForSchool(
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

        $topics = $this->repo->topicsForRoom($roomId, $this->activeSchoolId());

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

        $comments = $this->repo->commentsForTopic($topicId);

        return view('discussion.member.topic', compact('topic', 'comments'));
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
        abort_if($topic->is_closed, 403);

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
}
