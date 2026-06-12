<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualClass extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'title',
        'description',
        'class_id',
        'subject_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'zoom_meeting_id',
        'join_url',
        'start_url',
        'passcode',
        'audience',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'audience'         => 'array',
        'duration_minutes' => 'integer',
        'school_id'        => 'integer',
        'teacher_id'       => 'integer',
        'class_id'         => 'integer',
        'subject_id'       => 'integer',
        'created_by'       => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Presentation helpers ──────────────────────────────────────────────────

    public function statusLabel(): string
    {
        return match ($this->status) {
            'scheduled' => __('virtual_classes.status_scheduled'),
            'live'      => __('virtual_classes.status_live'),
            'ended'     => __('virtual_classes.status_ended'),
            'cancelled' => __('virtual_classes.status_cancelled'),
            default     => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'scheduled' => 'primary',
            'live'      => 'success',
            'ended'     => 'secondary',
            'cancelled' => 'danger',
            default     => 'light',
        };
    }

    /**
     * True when the class has not yet started and is not cancelled.
     */
    public function isUpcoming(): bool
    {
        return in_array($this->status, ['scheduled', 'live'])
            && $this->scheduled_at->isFuture();
    }

    /**
     * True from 10 minutes before scheduled_at until scheduled_at + duration_minutes.
     */
    public function isJoinable(): bool
    {
        if ($this->status === 'cancelled') {
            return false;
        }

        $openFrom  = $this->scheduled_at->copy()->subMinutes(10);
        $closeAt   = $this->scheduled_at->copy()->addMinutes($this->duration_minutes);

        return now()->between($openFrom, $closeAt);
    }
}
