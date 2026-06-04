<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanteenBlockedProduct extends Model
{
    protected $table = 'canteen_blocked_products';

    protected $fillable = [
        'student_id',
        'canteen_product_id',
        'blocked_by',
    ];
}
