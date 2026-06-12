<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'owner_id',
        'title',
        'date_from',
        'date_to',
        'days',
        'time_from',
        'time_to',
        'slot_minutes',
        'max_appointments',
        'location',
        'mode',
        'notes',
        'status',
        'booking_open',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'days'          => 'array',
            'date_from'     => 'date',
            'date_to'       => 'date',
            'booking_open'  => 'boolean',
            'slot_minutes'  => 'integer',
            'max_appointments' => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'schedule_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Effective status: if date_to is in the past → expired regardless of stored value.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'inactive') {
            return 'inactive';
        }
        if ($this->date_to && Carbon::parse($this->date_to)->isPast() && ! Carbon::parse($this->date_to)->isToday()) {
            return 'expired';
        }
        return 'active';
    }

    /**
     * Count of confirmed+requested appointments against this schedule.
     */
    public function bookedCount(): int
    {
        return $this->appointments()
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->count();
    }

    /**
     * Remaining slots; null when no max is set.
     */
    public function availableCount(): ?int
    {
        if ($this->max_appointments === null) {
            return null;
        }
        return max(0, $this->max_appointments - $this->bookedCount());
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForSchool($query, ?int $schoolId)
    {
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        return $query;
    }

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
}
