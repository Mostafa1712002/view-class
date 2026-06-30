<?php

namespace App\Modules\Qr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QrDevice extends Model
{
    use SoftDeletes;

    protected $table = 'qr_devices';

    protected $fillable = [
        'school_id', 'name', 'device_key', 'location', 'is_active', 'last_seen_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /** Secure, non-guessable key the IoT scanner presents to authenticate. */
    public static function generateKey(): string
    {
        return 'IOT-'.strtoupper(Str::random(28));
    }
}
