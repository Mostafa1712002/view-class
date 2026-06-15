<?php

namespace App\Modules\Qr\Models;

use Illuminate\Database\Eloquent\Model;

class QrDayClosure extends Model
{
    protected $table = 'qr_day_closures';

    protected $fillable = ['school_id', 'class_id', 'close_date', 'closed_by'];

    protected $casts = ['close_date' => 'date'];
}
