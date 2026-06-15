<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCreditLedger extends Model
{
    protected $table = 'sms_credit_ledger';

    protected $fillable = [
        'school_id',
        'type',
        'balance_before',
        'amount',
        'balance_after',
        'reason',
        'reference_type',
        'reference_id',
        'user_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
