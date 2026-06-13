<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'created_by',
        'related_student_id',
        'creator_role',
        'category',
        'subject',
        'body',
        'priority',
        'status',
        'assigned_to',
        'attachment_path',
        'last_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function relatedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_student_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open'        => __('support.status_open'),
            'in_progress' => __('support.status_in_progress'),
            'resolved'    => __('support.status_resolved'),
            'closed'      => __('support.status_closed'),
            default       => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'open'        => 'primary',
            'in_progress' => 'warning',
            'resolved'    => 'success',
            'closed'      => 'secondary',
            default       => 'light',
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'low'    => __('support.priority_low'),
            'normal' => __('support.priority_normal'),
            'high'   => __('support.priority_high'),
            'urgent' => __('support.priority_urgent'),
            default  => $this->priority,
        };
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'low'    => 'secondary',
            'normal' => 'info',
            'high'   => 'warning',
            'urgent' => 'danger',
            default  => 'light',
        };
    }
}
