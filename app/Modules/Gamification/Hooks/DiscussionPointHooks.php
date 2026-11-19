<?php

namespace App\Modules\Gamification\Hooks;

use App\Models\DiscussionComment;
use App\Models\DiscussionRoom;
use App\Models\DiscussionTopic;
use App\Models\User;
use App\Modules\Gamification\Actions\RevokeSourcePoints;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use Illuminate\Database\Eloquent\Model;

/** teacher.discussion_participation: distinct targeted-student commenters / targeted students. Source = the room. */
final class DiscussionPointHooks
{
    private const SOURCE = 'discussion_room';

    public function __construct(private SyncActivityPoints $sync, private RevokeSourcePoints $revoke) {}

    public function discussionRoomChanged(int $roomId): void
    {
        $room = DiscussionRoom::find($roomId);
        if (! $room) {
            return;
        }

        $targeted = self::targetedStudentIds($room);
        $units = 0.0;
        if ($targeted !== []) {
            $commenters = DiscussionComment::whereHas('topic', fn ($t) => $t->where('room_id', $roomId))
                ->whereIn('user_id', $targeted)->distinct()->count('user_id');
            $units = min(1.0, $commenters / count($targeted));
        }

        $this->sync->execute(new ActivityPoints('teacher.discussion_participation', (int) $room->created_by, self::SOURCE, $room->id, $units, auth()->id()));
    }

    /** For comment add/delete where the host only has the topic id. */
    public function discussionTopicChanged(int $topicId): void
    {
        $roomId = DiscussionTopic::withTrashed()->where('id', $topicId)->value('room_id');
        if ($roomId) {
            $this->discussionRoomChanged((int) $roomId);
        }
    }

    public function discussionRoomDeleted(int $roomId): void
    {
        $this->revoke->execute(self::SOURCE, $roomId, 'حذف غرفة النقاش');
    }

    /**
     * Students a room / live session is aimed at — same vocabulary as the models' scopeVisibleTo
     * (target_type, class_ids/grade_levels, targets pivot), resolved in bulk instead of per user.
     * Shared with VirtualClassPointHooks.
     *
     * @return list<int>
     */
    public static function targetedStudentIds(Model $m): array
    {
        $targets = $m->targets()->get();
        $ids = fn (string $kind) => $targets->where('kind', $kind)->pluck('target_id')->map(fn ($v) => (int) $v)->all();

        $students = fn () => User::where('school_id', $m->school_id)->whereHas('roles', fn ($q) => $q->where('slug', 'student'));

        $match = match ($m->target_type) {
            'all' => fn ($q) => $q,
            'specific_roles' => fn ($q) => $q->whereHas('roles', fn ($r) => $r->whereIn('roles.id', $ids('role'))),
            'job_titles' => fn ($q) => $q->whereIn('job_title_id', $ids('job_title')),
            'students' => self::studentsFilter((array) $m->class_ids, (array) $m->grade_levels),
            default => fn ($q) => $q->whereRaw('1 = 0'),
        };

        // explicit user picks count for every target type (scopeVisibleTo does the same)
        $picked = $students()->whereIn('id', $ids('user') ?: [0])->pluck('id');

        return $students()->where($match)->pluck('id')->merge($picked)->map(fn ($v) => (int) $v)->unique()->values()->all();
    }

    private static function studentsFilter(array $classIds, array $grades): \Closure
    {
        if ($classIds === [] && $grades === []) {
            return fn ($q) => $q;
        }

        return fn ($q) => $q->where(function ($n) use ($classIds, $grades) {
            $n->whereIn('class_room_id', $classIds ?: [0])
                ->orWhereHas('enrolledClasses', fn ($c) => $c->whereIn('classes.id', $classIds ?: [0]))
                ->orWhereHas('classRoom', fn ($c) => $c->whereIn('grade_level', $grades ?: [0]));
        });
    }
}
