<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsSender extends Model
{
    protected $table = 'sms_senders';

    /** Trello #243 — 7-state sender-name request workflow. */
    public const STATUSES = [
        'draft'        => 'مسودة',
        'submitted'    => 'تم الإرسال',
        'under_review' => 'قيد المراجعة',
        'needs_edit'   => 'يحتاج تعديل',
        'accepted'     => 'مقبول',
        'rejected'     => 'مرفوض',
        'active'       => 'مفعّل',
    ];

    public const KINDS = [
        'alerts'      => 'اسم مرسل التنبيهات (11 حرف)',
        'advertising' => 'اسم المرسل الإعلاني (8 أحرف)',
    ];

    protected $fillable = [
        'school_id',
        'name_ar',
        'name_en',
        'kind',
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

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** A sender usable for sending = accepted or active. */
    public function scopeUsable($q)
    {
        return $q->whereIn('status', ['accepted', 'active']);
    }
}
