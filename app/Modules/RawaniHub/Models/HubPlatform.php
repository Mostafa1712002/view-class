<?php

namespace App\Modules\RawaniHub\Models;

use Illuminate\Database\Eloquent\Model;

class HubPlatform extends Model
{
    protected $guarded = [];

    protected $hidden = ['secret'];

    protected $casts = [
        'secret' => 'encrypted',
        'products' => 'array',
        'capabilities' => 'array',
        'required_fields' => 'array',
        'is_enabled' => 'boolean',
        'last_ok_at' => 'datetime',
    ];
}
