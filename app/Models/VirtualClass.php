<?php

namespace App\Models;

use App\Modules\VirtualClasses\Models\VirtualClassTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualClass extends Model
{
    use SoftDeletes;

    /** Targeting modes — mirrors announcements / school-calendar. */
    public const TARGET_TYPES = [
        'all', 'students', 'teachers', 'parents', 'admins',
        'specific_users', 'specific_roles', 'job_titles',
    ];

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
        'target_type',
        'grade_levels',
        'class_ids',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'started_at'       => 'datetime',
        'audience'         => 'array',
        'grade_levels'     => 'array',
        'class_ids'        => 'array',
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

    public function targets(): HasMany
    {
        return $this->hasMany(VirtualClassTarget::class, 'virtual_class_id');
    }

    // ── Targeting / visibility ────────────────────────────────────────────────

    /**
     * The ONE rule that decides whether a session is visible to a given user.
     * Both the student list (forStudent) and the join gate derive from this so
     * they cannot drift. Mirrors the announcements targeting vocabulary.
     *
     * A parent inherits their children's grade/class context, so a session
     * targeted at a student's class also surfaces in the parent's account.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $roleIds    = $user->roles->pluck('id')->map(fn ($v) => (int) $v)->all();
        $jobTitleId = $user->job_title_id;
        [$ctxClassIds, $ctxGrades] = static::audienceContext($user);

        return $query->where(function (Builder $q) use ($user, $roleIds, $jobTitleId, $ctxClassIds, $ctxGrades) {
            // Everyone in the school.
            $q->where('target_type', 'all');

            // Explicit user pick (only written for the specific_users type).
            $q->orWhereHas('targets', fn ($t) => $t->where('kind', 'user')->where('target_id', $user->id));

            // Specific roles.
            if (! empty($roleIds)) {
                $q->orWhere(fn (Builder $s) => $s->where('target_type', 'specific_roles')
                    ->whereHas('targets', fn ($t) => $t->where('kind', 'role')->whereIn('target_id', $roleIds)));
            }

            // Specific job titles.
            if ($jobTitleId) {
                $q->orWhere(fn (Builder $s) => $s->where('target_type', 'job_titles')
                    ->whereHas('targets', fn ($t) => $t->where('kind', 'job_title')->where('target_id', $jobTitleId)));
            }

            // Students audience — visible to the student and to their parents,
            // narrowed by grade/class when configured (empty narrowing = all).
            if ($user->isStudent() || $user->isParent()) {
                $q->orWhere(function (Builder $s) use ($ctxClassIds, $ctxGrades) {
                    $s->where('target_type', 'students')
                      ->where(function (Builder $n) use ($ctxClassIds, $ctxGrades) {
                          $n->where(fn (Builder $e) => $e->whereNull('grade_levels')->whereNull('class_ids'));
                          foreach ($ctxClassIds as $cid) {
                              $n->orWhereJsonContains('class_ids', $cid);
                          }
                          foreach ($ctxGrades as $g) {
                              $n->orWhereJsonContains('grade_levels', $g);
                          }
                      });
                });
            }

            // Role-bucket audiences.
            if ($user->isTeacher()) {
                $q->orWhere('target_type', 'teachers');
            }
            if ($user->isParent()) {
                $q->orWhere('target_type', 'parents');
            }
            if ($user->isSuperAdmin() || $user->isSchoolAdmin()) {
                $q->orWhere('target_type', 'admins');
            }
        });
    }

    /**
     * Grade/class context used for the `students` audience match: a student's
     * own classes/grade, or — for a parent — the union of their children's.
     *
     * @return array{0: array<int>, 1: array<int>} [classIds, gradeLevels]
     */
    protected static function audienceContext(User $user): array
    {
        if ($user->isStudent()) {
            $grade = optional($user->classRoom)->grade_level;

            return [$user->enrolledClassIds(), $grade !== null ? [(int) $grade] : []];
        }

        if ($user->isParent()) {
            $classIds = [];
            $grades   = [];
            foreach ($user->children as $child) {
                $classIds = array_merge($classIds, $child->enrolledClassIds());
                $g = optional($child->classRoom)->grade_level;
                if ($g !== null) {
                    $grades[] = (int) $g;
                }
            }

            return [array_values(array_unique($classIds)), array_values(array_unique($grades))];
        }

        return [[], []];
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
