<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsSender extends Model
{
    protected $table = 'sms_senders';

    protected $fillable = [
        'school_id',
        'name_ar',
        'name_en',
        'status',
        'rejection_reason',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SmsSenderAttachment::class, 'sender_id');
    }
}
