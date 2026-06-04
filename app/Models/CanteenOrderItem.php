<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanteenOrderItem extends Model
{
    protected $table = 'canteen_order_items';

    protected $fillable = [
        'canteen_order_id',
        'canteen_product_id',
        'product_name',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CanteenOrder::class, 'canteen_order_id');
    }
}
