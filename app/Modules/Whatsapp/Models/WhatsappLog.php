<?php

namespace App\Modules\Whatsapp\Models;

use App\Models\Attendance;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'school_id',
        'student_id',
        'parent_id',
        'attendance_id',
        'broadcast_id',
        'recipient_user_id',
        'recipient_role',
        'to_number',
        'message_text',
        'message_type',
        'media_path',
        'status',
        'failure_reason',
        'provider',
        'sent_at',
        'triggered_by',
        'type',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending'        => 'بانتظار الإرسال',
        'sent'           => 'تم الإرسال',
        'failed'         => 'فشل',
        'skipped'        => 'متخطى',
        'invalid_number' => 'رقم غير صحيح',
        'no_number'      => 'لا يوجد رقم',
        'rejected'       => 'مرفوضة من المزود',
        'delivered'      => 'تم التسليم',
        'read'           => 'تمت القراءة',
    ];

    public const STATUS_COLORS = [
        'pending'        => 'warning',
        'sent'           => 'success',
        'failed'         => 'danger',
        'skipped'        => 'secondary',
        'invalid_number' => 'danger',
        'no_number'      => 'secondary',
        'rejected'       => 'danger',
        'delivered'      => 'info',
        'read'           => 'primary',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WhatsappBroadcast::class, 'broadcast_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }
}
