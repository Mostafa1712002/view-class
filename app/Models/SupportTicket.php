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
        'type',
        'category',
        'department',
        'subject',
        'body',
        'problem_url',
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

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SupportTicketStatusLog::class, 'ticket_id')->latest('id');
    }

    // ─── Constants (card #267) ──────────────────────────────────────────────────

    /** نوع التذكرة */
    public const TYPES = [
        'bug', 'inquiry', 'feature', 'activate_user', 'login_issue',
        'reports_issue', 'attendance_issue', 'certificates_issue', 'registration_issue',
    ];

    /** القسم */
    public const DEPARTMENTS = [
        'assignments', 'exams', 'virtual_classes', 'attendance', 'messages',
        'certificates', 'admissions', 'support', 'other',
    ];

    public const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public static function statusLabelFor(string $status): string
    {
        return match ($status) {
            'open'        => __('support.status_open'),
            'in_progress' => __('support.status_in_progress'),
            'resolved'    => __('support.status_resolved'),
            'closed'      => __('support.status_closed'),
            default       => $status,
        };
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor((string) $this->status);
    }

    public function typeLabel(): string
    {
        return $this->type ? __('support.type_' . $this->type) : '—';
    }

    public function departmentLabel(): string
    {
        return $this->department ? __('support.dept_' . $this->department) : '—';
    }

    /**
     * Derived "who replied last" state used by the admin stat cards
     * (admin-replied / user-replied), distinct from the status enum.
     */
    public function derivedReplyState(): ?string
    {
        $last = $this->replies->last();
        if (! $last) {
            return null;
        }

        return $last->is_staff ? 'admin_replied' : 'user_replied';
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
