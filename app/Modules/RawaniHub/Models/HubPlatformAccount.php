<?php

namespace App\Modules\RawaniHub\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HubPlatformAccount extends Model
{
    protected $guarded = [];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'encrypted',
        'managed' => 'boolean',
        'enabled' => 'boolean',
        'needs_sync' => 'boolean',
        'access' => 'array',
        'synced_at' => 'datetime',
        'seen_at' => 'datetime',
    ];

    public function platform()
    {
        return $this->belongsTo(HubPlatform::class, 'platform_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
