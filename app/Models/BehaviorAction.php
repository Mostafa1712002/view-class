<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BehaviorAction extends Model
{
    use SoftDeletes;

    protected $table = 'behavior_actions';

    protected $fillable = [
        'behavior_id',
        'school_id',
        'description',
        'points',
        'point_type',
        'notify_parent',
        'needs_followup',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'points' => 'integer',
        'notify_parent' => 'boolean',
        'needs_followup' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const POINT_TYPES = ['add', 'deduct'];

    public function behavior(): BelongsTo
    {
        return $this->belongsTo(Behavior::class, 'behavior_id');
    }

    /** Signed point delta this action applies. */
    public function signedPoints(): int
    {
        return $this->point_type === 'deduct' ? -abs($this->points) : abs($this->points);
    }
}
