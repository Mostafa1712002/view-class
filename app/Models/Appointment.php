<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'schedule_id',
        'student_id',
        'booked_by',
        'bookable_role_id',
        'target_user_id',
        'subject_id',
        'reason',
        'appointment_date',
        'appointment_time',
        'contact_method',
        'notes',
        'attachment_path',
        'status',
        'decision_by',
        'decision_at',
        'decision_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'decision_at'      => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AppointmentSchedule::class, 'schedule_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function bookableRole(): BelongsTo
    {
        return $this->belongsTo(AppointmentBookableRole::class, 'bookable_role_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForSchool($query, ?int $schoolId)
    {
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        return $query;
    }
}
