<?php

namespace App\Modules\Discussion\Repositories;

use App\Models\DiscussionComment;
use App\Models\DiscussionRoom;
use App\Models\DiscussionTopic;
use App\Modules\Discussion\Repositories\Contracts\DiscussionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DiscussionEloquentRepository implements DiscussionRepository
{
    // ── Rooms ────────────────────────────────────────────────────────────────

    public function roomsForSchool(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DiscussionRoom::query()
            ->where('school_id', $schoolId)
            ->with(['creator:id,name,name_ar']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findRoom(int $id): ?DiscussionRoom
    {
        return DiscussionRoom::with(['creator:id,name,name_ar'])->find($id);
    }

    public function createRoom(array $data): DiscussionRoom
    {
        return DiscussionRoom::create($data);
    }

    public function updateRoom(int $id, array $data): DiscussionRoom
    {
        $room = DiscussionRoom::findOrFail($id);
        $room->update($data);

        return $room->fresh();
    }

    public function closeRoom(int $id): DiscussionRoom
    {
        $room = DiscussionRoom::findOrFail($id);
        $room->update(['status' => 'closed']);

        return $room->fresh();
    }

    public function deleteRoom(int $id): void
    {
        DiscussionRoom::findOrFail($id)->delete();
    }

    // ── Topics ───────────────────────────────────────────────────────────────

    public function topicsForRoom(int $roomId, int $schoolId, int $perPage = 20): LengthAwarePaginator
    {
        return DiscussionTopic::query()
            ->where('room_id', $roomId)
            ->where('school_id', $schoolId)
            ->with(['creator:id,name,name_ar'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_activity_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findTopic(int $id): ?DiscussionTopic
    {
        return DiscussionTopic::with([
            'creator:id,name,name_ar',
            'room:id,school_id,title,status',
        ])->find($id);
    }

    public function createTopic(array $data): DiscussionTopic
    {
        $topic = DiscussionTopic::create(array_merge($data, [
            'last_activity_at' => now(),
        ]));

        // Increment room topics_count
        DiscussionRoom::where('id', $topic->room_id)->increment('topics_count');

        return $topic;
    }

    public function pinTopic(int $id): DiscussionTopic
    {
        $topic = DiscussionTopic::findOrFail($id);
        $topic->update(['is_pinned' => ! $topic->is_pinned]);

        return $topic->fresh();
    }

    public function closeTopic(int $id): DiscussionTopic
    {
        $topic = DiscussionTopic::findOrFail($id);
        $topic->update(['is_closed' => 1]);

        return $topic->fresh();
    }

    public function deleteTopic(int $id): void
    {
        $topic = DiscussionTopic::findOrFail($id);
        // Decrement room counter before deleting
        DiscussionRoom::where('id', $topic->room_id)->decrement('topics_count');
        $topic->delete();
    }

    // ── Comments ─────────────────────────────────────────────────────────────

    public function commentsForTopic(int $topicId): Collection
    {
        return DiscussionComment::query()
            ->where('topic_id', $topicId)
            ->with(['user:id,name,name_ar'])
            ->orderBy('id')
            ->get();
    }

    public function addComment(array $data): DiscussionComment
    {
        $comment = DiscussionComment::create($data);

        // Update topic counters
        DiscussionTopic::where('id', $comment->topic_id)->update([
            'comments_count'   => \DB::raw('comments_count + 1'),
            'last_activity_at' => now(),
        ]);

        return $comment;
    }

    public function findComment(int $id): ?DiscussionComment
    {
        return DiscussionComment::find($id);
    }

    public function deleteComment(int $id): void
    {
        $comment = DiscussionComment::findOrFail($id);
        $topicId = $comment->topic_id;
        $comment->delete();

        // Decrement topic comments_count (floor at 0)
        DiscussionTopic::where('id', $topicId)
            ->where('comments_count', '>', 0)
            ->decrement('comments_count');
    }
}
