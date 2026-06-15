<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'platform',
        'started_at',
        'zoom_meeting_id',
        'join_url',
        'start_url',
        'passcode',
        'external_url',
        'audience',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'started_at'       => 'datetime',
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

    public function attendees(): HasMany
    {
        return $this->hasMany(\App\Modules\VirtualClasses\Models\VirtualClassAttendee::class, 'virtual_class_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
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
     * Card rule: the join button appears only 5 minutes before scheduled_at,
     * and stays open until scheduled_at + duration_minutes.
     */
    public function isJoinable(): bool
    {
        if (in_array($this->status, ['cancelled', 'ended'], true)) {
            return false;
        }

        $openFrom = $this->scheduled_at->copy()->subMinutes(5);
        $closeAt  = $this->scheduled_at->copy()->addMinutes($this->duration_minutes);

        return now()->between($openFrom, $closeAt);
    }

    public function platformLabel(): string
    {
        return match ($this->platform) {
            'zoom'     => 'Zoom',
            'teams'    => 'Microsoft Teams',
            'external' => __('virtual_classes.platform_external'),
            'internal' => __('virtual_classes.platform_internal'),
            default    => $this->platform ?: 'Zoom',
        };
    }

    /**
     * The URL a participant opens to enter the meeting, by platform.
     */
    public function participantUrl(): ?string
    {
        return match ($this->platform) {
            'external', 'teams' => $this->external_url,
            default             => $this->join_url,
        };
    }
}
