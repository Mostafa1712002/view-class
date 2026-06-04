<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Behavior extends Model
{
    use SoftDeletes;

    protected $table = 'behaviors';

    protected $fillable = [
        'behavior_group_id',
        'school_id',
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(BehaviorGroup::class, 'behavior_group_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(BehaviorAction::class, 'behavior_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
