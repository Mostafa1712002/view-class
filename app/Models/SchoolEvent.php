<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_events';

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'event_type',
        'start_date',
        'end_date',
        'all_day',
        'start_time',
        'end_time',
        'color',
        'audience',
        'location',
        'created_by',
    ];

    protected $casts = [
        'audience'   => 'array',
        'all_day'    => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Events visible to a given role key.
     * An event targeted at 'all' is visible to every role.
     * An event targeted at a specific role is only visible to that role.
     */
    public function scopeVisibleTo(Builder $query, string $roleKey): Builder
    {
        return $query->where(function (Builder $q) use ($roleKey) {
            $q->whereJsonContains('audience', 'all')
              ->orWhereJsonContains('audience', $roleKey);
        });
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function eventTypeLabel(): string
    {
        return match ($this->event_type) {
            'holiday'  => __('school_calendar.type_holiday'),
            'exam'     => __('school_calendar.type_exam'),
            'activity' => __('school_calendar.type_activity'),
            'meeting'  => __('school_calendar.type_meeting'),
            default    => __('school_calendar.type_other'),
        };
    }

    public function eventTypeColor(): string
    {
        if ($this->color) {
            return $this->color;
        }

        return match ($this->event_type) {
            'holiday'  => '#e74c3c',
            'exam'     => '#e67e22',
            'activity' => '#2ecc71',
            'meeting'  => '#3498db',
            default    => '#95a5a6',
        };
    }
}
