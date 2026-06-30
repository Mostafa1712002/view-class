<?php

namespace App\Modules\Discussion\Models;

use App\Models\DiscussionRoom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-room targeting row (kind: user | role | job_title) — mirrors
 * virtual_class_targets. Grade/class narrowing lives on the discussion_rooms
 * row itself; this table holds the explicit user/role/job-title picks.
 */
class DiscussionRoomTarget extends Model
{
    protected $table = 'discussion_room_targets';

    protected $fillable = ['discussion_room_id', 'kind', 'target_id'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(DiscussionRoom::class, 'discussion_room_id');
    }
}
