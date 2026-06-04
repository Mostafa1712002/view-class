<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BehaviorRecord extends Model
{
    use SoftDeletes;

    protected $table = 'behavior_records';

    protected $fillable = [
        'school_id',
        'scope',
        'subject_user_id',
        'behavior_id',
        'behavior_action_id',
        'points',
        'note',
        'needs_followup',
        'notified_parent',
        'recorded_by',
    ];

    protected $casts = [
        'points' => 'integer',
        'needs_followup' => 'boolean',
        'notified_parent' => 'boolean',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function behavior(): BelongsTo
    {
        return $this->belongsTo(Behavior::class, 'behavior_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(BehaviorAction::class, 'behavior_action_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
