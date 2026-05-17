<?php

namespace App\Modules\SmsServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsSenderAttachment extends Model
{
    protected $table = 'sms_sender_attachments';

    protected $fillable = [
        'sender_id',
        'provider',
        'file_path',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SmsSender::class, 'sender_id');
    }
}
