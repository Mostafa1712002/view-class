<?php

namespace App\Modules\RawaniHub\Models;

use Illuminate\Database\Eloquent\Model;

class HubPaymentEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['payload' => 'array'];
}
