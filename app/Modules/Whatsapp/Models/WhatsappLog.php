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
        'to_number',
        'message_text',
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
        'pending' => 'قيد الإرسال',
        'sent'    => 'مُرسل',
        'failed'  => 'فشل',
        'skipped' => 'متخطى',
    ];

    public const STATUS_COLORS = [
        'pending' => 'warning',
        'sent'    => 'success',
        'failed'  => 'danger',
        'skipped' => 'secondary',
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

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }
}
