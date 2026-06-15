<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionRoom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'instructions',
        'scope_type',
        'scope_id',
        'audience',
        'allow_topics',
        'allow_comments',
        'requires_approval',
        'status',
        'created_by',
        'topics_count',
        'comments_count',
        'last_activity_at',
    ];

    protected $casts = [
        'audience'          => 'array',
        'scope_id'          => 'integer',
        'school_id'         => 'integer',
        'created_by'        => 'integer',
        'allow_topics'      => 'boolean',
        'allow_comments'    => 'boolean',
        'requires_approval' => 'boolean',
        'last_activity_at'  => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(DiscussionTopic::class, 'room_id');
    }

    /**
     * Scope queries to a specific school.
     */
    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
