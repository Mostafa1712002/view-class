<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'topic_id',
        'school_id',
        'user_id',
        'body',
    ];

    protected $casts = [
        'topic_id'  => 'integer',
        'school_id' => 'integer',
        'user_id'   => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(DiscussionTopic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
