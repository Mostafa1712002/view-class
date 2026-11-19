<?php

namespace App\Modules\RawaniHub\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HubPayment extends Model
{
    protected $guarded = [];

    protected $casts = ['paid_at' => 'datetime', 'refunded_at' => 'datetime', 'last_checked_at' => 'datetime'];

    public function subscription()
    {
        return $this->belongsTo(HubSubscription::class, 'subscription_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
