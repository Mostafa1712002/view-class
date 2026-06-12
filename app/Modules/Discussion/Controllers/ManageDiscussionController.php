<?php

namespace App\Modules\Discussion\Controllers;

use App\Http\Controllers\Controller;
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
        $rooms   = $this->repo->roomsForSchool($this->activeSchoolId(), $filters);

        return view('discussion.manage.index', compact('rooms', 'filters'));
    }

    public function create(): View
    {
        return view('discussion.manage.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'scope_type'  => ['nullable', 'string', 'max:20'],
            'audience'    => ['nullable', 'array'],
        ]);

        $this->repo->createRoom(array_merge($data, [
            'school_id'  => $this->activeSchoolId(),
            'created_by' => auth()->id(),
            'scope_type' => $data['scope_type'] ?? 'school',
            'audience'   => $data['audience'] ?? ['all'],
            'status'     => 'active',
        ]));

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
            'title'       => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
        ]);

        $this->repo->updateRoom($id, $data);

        return redirect()
            ->route('manage.discussion-rooms.index')
            ->with('success', __('discussion.flash_room_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $room = $this->repo->findRoom($id);
        abort_if(! $room, 404);
        $this->assertSchool($room->school_id);

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

        return redirect()
            ->route('manage.discussion-rooms.index')
            ->with('success', __('discussion.flash_room_closed'));
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

    public function deleteTopic(int $topicId): RedirectResponse
    {
        $topic = $this->repo->findTopic($topicId);
        abort_if(! $topic, 404);
        $this->assertSchool($topic->school_id);

        $roomId = $topic->room_id;
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
        abort_if($resourceSchoolId !== $this->activeSchoolId(), 403);
    }
}
