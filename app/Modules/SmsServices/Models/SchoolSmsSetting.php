<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSmsSetting extends Model
{
    protected $table = 'school_sms_settings';

    protected $fillable = [
        'school_id',
        'provider',
        'api_key',
        'api_secret',
        'is_active',
        'sms_used',
        'sms_total',
        'default_sender_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sms_used' => 'integer',
        'sms_total' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function defaultSender(): BelongsTo
    {
        return $this->belongsTo(SmsSender::class, 'default_sender_id');
    }
}
