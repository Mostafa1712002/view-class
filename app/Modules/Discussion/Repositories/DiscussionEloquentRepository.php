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

    public function roomsForSchool(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DiscussionRoom::query()
            ->when($schoolId !== null, fn ($q) => $q->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId)))
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

    public function reopenRoom(int $id): DiscussionRoom
    {
        $room = DiscussionRoom::findOrFail($id);
        $room->update(['status' => 'active']);

        return $room->fresh();
    }

    public function toggleRoomComments(int $id): DiscussionRoom
    {
        $room = DiscussionRoom::findOrFail($id);
        $room->update(['allow_comments' => ! $room->allow_comments]);

        return $room->fresh();
    }

    public function deleteRoom(int $id): void
    {
        DiscussionRoom::findOrFail($id)->delete();
    }

    // ── Topics ───────────────────────────────────────────────────────────────

    public function topicsForRoom(int $roomId, ?int $schoolId, int $perPage = 20, bool $includeHidden = false): LengthAwarePaginator
    {
        return DiscussionTopic::query()
            ->where('room_id', $roomId)
            ->when($schoolId !== null, fn ($q) => $q->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId)))
            ->when(! $includeHidden, fn ($q) => $q->where('is_hidden', false))
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
            'room:id,school_id,title,status,allow_topics,allow_comments',
        ])->find($id);
    }

    public function createTopic(array $data): DiscussionTopic
    {
        $topic = DiscussionTopic::create(array_merge($data, [
            'last_activity_at' => now(),
        ]));

        // Increment room topics_count + bubble last activity
        DiscussionRoom::where('id', $topic->room_id)->update([
            'topics_count'     => \DB::raw('topics_count + 1'),
            'last_activity_at' => now(),
        ]);

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

    public function toggleTopicComments(int $id): DiscussionTopic
    {
        $topic = DiscussionTopic::findOrFail($id);
        $topic->update(['comments_closed' => ! $topic->comments_closed]);

        return $topic->fresh();
    }

    public function toggleTopicHidden(int $id): DiscussionTopic
    {
        $topic = DiscussionTopic::findOrFail($id);
        $topic->update(['is_hidden' => ! $topic->is_hidden]);

        return $topic->fresh();
    }

    public function deleteTopic(int $id): void
    {
        $topic = DiscussionTopic::findOrFail($id);
        // Decrement room counters before deleting
        DiscussionRoom::where('id', $topic->room_id)
            ->where('topics_count', '>', 0)
            ->decrement('topics_count');
        DiscussionRoom::where('id', $topic->room_id)
            ->where('comments_count', '>=', $topic->comments_count)
            ->decrement('comments_count', $topic->comments_count);
        $topic->delete();
    }

    public function roomReport(int $roomId): array
    {
        $room = DiscussionRoom::findOrFail($roomId);

        $topicCount   = DiscussionTopic::where('room_id', $roomId)->count();
        $topicIds     = DiscussionTopic::where('room_id', $roomId)->pluck('id');
        $commentCount = DiscussionComment::whereIn('topic_id', $topicIds)->count();

        $participantIds = DiscussionComment::whereIn('topic_id', $topicIds)
            ->pluck('user_id')
            ->merge(DiscussionTopic::where('room_id', $roomId)->pluck('created_by'))
            ->unique()
            ->filter()
            ->values();

        $topTopics = DiscussionTopic::where('room_id', $roomId)
            ->with('creator:id,name,name_ar')
            ->orderByDesc('comments_count')
            ->limit(5)
            ->get();

        return [
            'room'              => $room,
            'topic_count'       => $topicCount,
            'comment_count'     => $commentCount,
            'participant_count' => $participantIds->count(),
            'last_activity_at'  => $room->last_activity_at,
            'top_topics'        => $topTopics,
        ];
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

        // Bubble up to the room (count + last activity)
        $roomId = DiscussionTopic::where('id', $comment->topic_id)->value('room_id');
        if ($roomId) {
            DiscussionRoom::where('id', $roomId)->update([
                'comments_count'   => \DB::raw('comments_count + 1'),
                'last_activity_at' => now(),
            ]);
        }

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
        $roomId  = DiscussionTopic::where('id', $topicId)->value('room_id');
        $comment->delete();

        // Decrement topic comments_count (floor at 0)
        DiscussionTopic::where('id', $topicId)
            ->where('comments_count', '>', 0)
            ->decrement('comments_count');

        // Decrement room comments_count (floor at 0)
        if ($roomId) {
            DiscussionRoom::where('id', $roomId)
                ->where('comments_count', '>', 0)
                ->decrement('comments_count');
        }
    }
}
