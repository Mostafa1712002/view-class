<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BehaviorGroup extends Model
{
    use SoftDeletes;

    protected $table = 'behavior_groups';

    protected $fillable = [
        'school_id',
        'scope',
        'name',
        'type',
        'available_for_teacher',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'available_for_teacher' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Behaviour scope tabs (الطلاب / المعلمين). */
    public const SCOPES = ['student', 'teacher'];

    /** Group polarity (إيجابي / سلبي). */
    public const TYPES = ['positive', 'negative'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Behaviours that belong to this group (card #115). */
    public function behaviors(): HasMany
    {
        return $this->hasMany(Behavior::class, 'behavior_group_id');
    }
}
