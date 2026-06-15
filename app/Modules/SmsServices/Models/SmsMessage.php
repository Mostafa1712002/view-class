<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $table = 'sms_messages';

    /** The 9 reportable statuses (Trello #240). */
    public const STATUSES = [
        'queued'         => 'بانتظار الإرسال',
        'sent'           => 'تم الإرسال',
        'failed'         => 'فشل',
        'invalid_number' => 'رقم غير صحيح',
        'no_number'      => 'لا يوجد رقم',
        'no_credit'      => 'لا يوجد رصيد',
        'rejected'       => 'مرفوضة',
        'delivered'      => 'تم التسليم',
        'read'           => 'تمت القراءة',
    ];

    protected $fillable = [
        'school_id',
        'batch_id',
        'template_id',
        'sender_id',
        'recipient_user_id',
        'recipient',
        'recipient_name',
        'recipient_role',
        'body',
        'status',
        'provider',
        'channel',
        'message_count',
        'credit_charged',
        'triggered_by',
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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SmsBatch::class, 'batch_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
