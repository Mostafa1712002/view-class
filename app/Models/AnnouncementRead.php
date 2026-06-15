<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRead extends Model
{
    protected $fillable = [
        'announcement_id',
        'user_id',
        'role',
        'school_id',
        'viewed_at',
        'read_confirmed_at',
        'ip_address',
        'device',
    ];

    protected $casts = [
        'viewed_at'         => 'datetime',
        'read_confirmed_at' => 'datetime',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
