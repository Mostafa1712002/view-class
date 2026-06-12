<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionTopic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_id',
        'school_id',
        'title',
        'body',
        'created_by',
        'is_pinned',
        'is_closed',
        'comments_count',
        'last_activity_at',
    ];

    protected $casts = [
        'is_pinned'        => 'boolean',
        'is_closed'        => 'boolean',
        'last_activity_at' => 'datetime',
        'room_id'          => 'integer',
        'school_id'        => 'integer',
        'created_by'       => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(DiscussionRoom::class, 'room_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DiscussionComment::class, 'topic_id');
    }

    /**
     * Scope queries to a specific school.
     */
    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
