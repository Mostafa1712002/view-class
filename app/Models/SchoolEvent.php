<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_events';

    /** Event types supported by the school calendar (card #233). */
    public const TYPES = [
        'general', 'private', 'holiday', 'exam', 'meeting',
        'activity', 'admin', 'virtual_class', 'alert', 'occasion',
    ];

    /** Targeting modes (card #233). */
    public const TARGET_SCHOOL   = 'school';   // whole school, narrowed by audience roles
    public const TARGET_SPECIFIC = 'specific'; // chosen grades / classes / users

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
        'target_type',
        'grade_levels',
        'class_ids',
        'notify',
        'remind_before',
        'remind_minutes',
        'reminded_at',
        'location',
        'created_by',
    ];

    protected $casts = [
        'audience'      => 'array',
        'grade_levels'  => 'array',
        'class_ids'     => 'array',
        'all_day'       => 'boolean',
        'notify'        => 'boolean',
        'remind_before' => 'boolean',
        'start_date'    => 'date',
        'end_date'      => 'date',
        'reminded_at'   => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SchoolEventTarget::class, 'school_event_id');
    }

    // ─── Visibility ───────────────────────────────────────────────────────────

    /**
     * Whether this event should appear on a given user's personal calendar.
     * School-admins / super-admins see every event in their scope; for everyone
     * else the targeting (audience role for whole-school events, or the explicit
     * grade / class / user lists for specific events) is enforced.
     */
    public function isVisibleTo(User $user): bool
    {
        // Staff with management reach see all events in the school.
        if ($user->isSuperAdmin() || $user->canDo('calendar.create_event')) {
            return true;
        }

        if (($this->target_type ?? self::TARGET_SCHOOL) === self::TARGET_SPECIFIC) {
            // Explicit user target
            $userTargetIds = $this->relationLoaded('targets')
                ? $this->targets->where('kind', 'user')->pluck('target_id')->all()
                : $this->targets()->where('kind', 'user')->pluck('target_id')->all();
            if (in_array($user->id, array_map('intval', $userTargetIds), true)) {
                return true;
            }

            // Grade / class targeting (students)
            $grades  = $this->grade_levels ?: [];
            $classes = array_map('intval', $this->class_ids ?: []);
            if (! empty($classes) && array_intersect($classes, $user->enrolledClassIds())) {
                return true;
            }
            if (! empty($grades)) {
                $userGrade = optional($user->classRoom)->grade_level;
                if ($userGrade !== null && in_array((string) $userGrade, array_map('strval', $grades), true)) {
                    return true;
                }
            }

            return false;
        }

        // Whole-school event: narrow by audience role keys.
        $audience = $this->audience ?: ['all'];
        if (in_array('all', $audience, true)) {
            return true;
        }

        $roleKey = match (true) {
            $user->isStudent() => 'students',
            $user->isParent()  => 'parents',
            $user->isTeacher() => 'teachers',
            default            => 'staff',
        };

        return in_array($roleKey, $audience, true);
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
        $key = in_array($this->event_type, self::TYPES, true) ? $this->event_type : 'general';

        return __('school_calendar.type_' . $key);
    }

    public function eventTypeColor(): string
    {
        if ($this->color) {
            return $this->color;
        }

        return match ($this->event_type) {
            'general'       => '#3498db',
            'private'       => '#7f8c8d',
            'holiday'       => '#e74c3c',
            'exam'          => '#e67e22',
            'meeting'       => '#2980b9',
            'activity'      => '#2ecc71',
            'admin'         => '#34495e',
            'virtual_class' => '#8b5cf6',
            'alert'         => '#f1c40f',
            'occasion'      => '#9b59b6',
            default         => '#95a5a6',
        };
    }
}
