<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanteenBalanceTransaction extends Model
{
    protected $table = 'canteen_balance_transactions';

    protected $fillable = [
        'school_id',
        'student_id',
        'type',
        'amount',
        'balance_after',
        'note',
        'source',
        'performed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
