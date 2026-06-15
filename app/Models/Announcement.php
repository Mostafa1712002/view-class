<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'created_by',
        'title',
        'body',
        'type',
        'target_type',
        'grade_levels',
        'class_ids',
        'subject_ids',
        'status',
        'starts_at',
        'ends_at',
        'show_on_login',
        'require_read_ack',
        'notify_internal',
        'notify_sms',
        'notify_whatsapp',
        'published_at',
    ];

    protected $casts = [
        'grade_levels'     => 'array',
        'class_ids'        => 'array',
        'subject_ids'      => 'array',
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
        'published_at'     => 'datetime',
        'show_on_login'    => 'boolean',
        'require_read_ack' => 'boolean',
        'notify_internal'  => 'boolean',
        'notify_sms'       => 'boolean',
        'notify_whatsapp'  => 'boolean',
    ];

    public const TYPES = ['normal', 'important', 'popup'];

    public const TARGET_TYPES = [
        'all', 'students', 'teachers', 'parents', 'admins', 'specific_users', 'specific_roles',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    // ── Derived status ──────────────────────────────────────────────────────

    /**
     * Effective status derived from base status + display window:
     *   draft | scheduled | active | expired | stopped | deleted
     */
    public function effectiveStatus(): string
    {
        if ($this->trashed()) {
            return 'deleted';
        }
        if ($this->status === 'draft') {
            return 'draft';
        }
        if ($this->status === 'stopped') {
            return 'stopped';
        }
        // published:
        $now = now();
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }
        return 'active';
    }

    /** Whether the display window currently includes now() (status published). */
    public function isWithinWindow(): bool
    {
        $now = now();
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }
        return true;
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && $this->isWithinWindow();
    }
}
