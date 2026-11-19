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
        'password_reset_pending' => 'boolean',
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

    /** The existing account's id from a 409's details, kept inside last_error (best effort, null when absent). */
    public function conflictExternalId(): ?string
    {
        return preg_match('/"external_id":"?([^",}]+)/', (string) $this->last_error, $m) ? $m[1] : null;
    }
}
