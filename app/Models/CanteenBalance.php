<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanteenBalance extends Model
{
    protected $table = 'canteen_balances';

    protected $fillable = [
        'school_id',
        'student_id',
        'balance',
        'daily_limit',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'daily_limit' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
