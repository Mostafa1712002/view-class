<?php

namespace App\Modules\Gamification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PointReward extends Model
{
    public const EARNED = 'earned';

    public const DELIVERED = 'delivered';

    protected $guarded = [];

    protected $casts = ['delivered_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function period()
    {
        return $this->belongsTo(PointRewardPeriod::class, 'period_id')->withTrashed();
    }
}
