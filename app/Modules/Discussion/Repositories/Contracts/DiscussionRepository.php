<?php

namespace App\Modules\Discussion\Repositories\Contracts;

use App\Models\DiscussionComment;
use App\Models\DiscussionRoom;
use App\Models\DiscussionTopic;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DiscussionRepository
{
    // ── Rooms ────────────────────────────────────────────────────────────────

    /**
     * Paginated list of rooms for a school with optional filters. Used by the
     * STAFF management screen — returns every room regardless of targeting.
     *
     * @param array $filters  Optional: status
     */
    public function roomsForSchool(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Paginated list of rooms a given user is allowed to see (member side).
     * Applies the same targeting rule as the room-view gate.
     */
    public function roomsVisibleTo(User $user, ?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Whether the targeting rule lets this user see the room (member room view).
     */
    public function isRoomVisibleTo(DiscussionRoom $room, User $user): bool;

    public function findRoom(int $id): ?DiscussionRoom;

    /**
     * @param array{user?:array<int>,role?:array<int>,job_title?:array<int>} $targets
     */
    public function createRoom(array $data, array $targets = []): DiscussionRoom;

    /**
     * @param array{user?:array<int>,role?:array<int>,job_title?:array<int>}|null $targets
     */
    public function updateRoom(int $id, array $data, ?array $targets = null): DiscussionRoom;

    /**
     * Set status to 'closed'.
     */
    public function closeRoom(int $id): DiscussionRoom;

    /**
     * Set status back to 'active'.
     */
    public function reopenRoom(int $id): DiscussionRoom;

    /**
     * Toggle the room-level allow_comments flag.
     */
    public function toggleRoomComments(int $id): DiscussionRoom;

    /**
     * Toggle the room-level allow_topics flag (إيقاف الموضوعات الجديدة).
     */
    public function toggleRoomTopics(int $id): DiscussionRoom;

    public function deleteRoom(int $id): void;

    // ── Topics ───────────────────────────────────────────────────────────────

    /**
     * Paginated topics inside a room, school-scoped.
     * Pinned topics first, then by last_activity_at desc.
     *
     * @param bool $includeHidden  Staff see hidden topics; members do not.
     */
    public function topicsForRoom(int $roomId, ?int $schoolId, int $perPage = 20, bool $includeHidden = false): LengthAwarePaginator;

    public function findTopic(int $id): ?DiscussionTopic;

    /**
     * Creates the topic AND increments room.topics_count + sets last_activity_at.
     */
    public function createTopic(array $data): DiscussionTopic;

    /**
     * Toggle is_pinned flag.
     */
    public function pinTopic(int $id): DiscussionTopic;

    /**
     * Set is_closed = 1.
     */
    public function closeTopic(int $id): DiscussionTopic;

    /**
     * Toggle the per-topic comments_closed flag (إيقاف/تشغيل التعليق).
     */
    public function toggleTopicComments(int $id): DiscussionTopic;

    /**
     * Toggle the per-topic is_hidden flag (إخفاء/إظهار).
     */
    public function toggleTopicHidden(int $id): DiscussionTopic;

    public function deleteTopic(int $id): void;

    /**
     * Aggregate stats for a room's report (topics, comments, participants).
     */
    public function roomReport(int $roomId): array;

    // ── Comments ─────────────────────────────────────────────────────────────

    /**
     * All comments for a topic (not paginated — threads are usually short).
     *
     * @return \Illuminate\Database\Eloquent\Collection<DiscussionComment>
     */
    public function commentsForTopic(int $topicId);

    /**
     * Adds a comment AND increments topic.comments_count + topic.last_activity_at.
     */
    public function addComment(array $data): DiscussionComment;

    public function findComment(int $id): ?DiscussionComment;

    public function deleteComment(int $id): void;
}
