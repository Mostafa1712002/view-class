<?php

namespace App\Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;

class PointRuleSetting extends Model
{
    /** school_id of the platform-wide row. */
    public const PLATFORM = 0;

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'allow_school_override' => 'boolean',
        'options' => 'array',
    ];
}
