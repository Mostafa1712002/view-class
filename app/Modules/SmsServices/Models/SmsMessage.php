<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $table = 'sms_messages';

    protected $fillable = [
        'school_id',
        'sender_id',
        'recipient',
        'body',
        'status',
        'provider',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SmsSender::class, 'sender_id');
    }
}
