<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CanteenOrder extends Model
{
    use SoftDeletes;

    protected $table = 'canteen_orders';

    protected $fillable = [
        'school_id',
        'canteen_id',
        'student_id',
        'status',
        'total',
        'charged',
        'note',
        'placed_by',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'charged' => 'boolean',
    ];

    public const STATUSES = ['new', 'confirmed', 'prepared', 'delivered', 'cancelled'];

    /** Allowed forward transitions for the order status machine. */
    public const FLOW = [
        'new' => ['confirmed', 'cancelled'],
        'confirmed' => ['prepared', 'cancelled'],
        'prepared' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class, 'canteen_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CanteenOrderItem::class, 'canteen_order_id');
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::FLOW[$this->status] ?? [], true);
    }
}
