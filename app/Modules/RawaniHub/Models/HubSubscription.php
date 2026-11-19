<?php

namespace App\Modules\RawaniHub\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HubSubscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'items' => 'array',
        'price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function package()
    {
        return $this->belongsTo(HubPackage::class, 'package_id')->withTrashed();
    }

    public function payment()
    {
        return $this->hasOne(HubPayment::class, 'subscription_id');
    }
}
