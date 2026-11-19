<?php

namespace App\Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointRewardPeriod extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];
}
