<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsBatch extends Model
{
    protected $table = 'sms_batches';

    protected $fillable = [
        'school_id',
        'sender_user_id',
        'sender_id',
        'sender_name_snapshot',
        'template_id',
        'name',
        'source',
        'total_recipients',
        'total_messages',
        'sent_count',
        'failed_count',
        'queued_count',
        'skipped_count',
        'credit_charged',
        'provider',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SmsSender::class, 'sender_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'batch_id');
    }
}
