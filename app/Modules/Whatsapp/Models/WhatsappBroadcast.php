<?php

namespace App\Modules\Whatsapp\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappBroadcast extends Model
{
    use SoftDeletes;

    protected $table = 'whatsapp_broadcasts';

    protected $fillable = [
        'school_id',
        'sender_id',
        'message_type',
        'body',
        'media_path',
        'media_original_name',
        'audience_label',
        'total_recipients',
        'sent_count',
        'failed_count',
        'skipped_count',
        'provider',
    ];

    protected $casts = [
        'total_recipients' => 'integer',
        'sent_count'       => 'integer',
        'failed_count'     => 'integer',
        'skipped_count'    => 'integer',
    ];

    public const MESSAGE_TYPES = [
        'text'  => 'رسالة نصية',
        'image' => 'صورة',
        'pdf'   => 'ملف PDF',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class, 'broadcast_id');
    }

    public function getMessageTypeLabelAttribute(): string
    {
        return self::MESSAGE_TYPES[$this->message_type] ?? $this->message_type;
    }
}
