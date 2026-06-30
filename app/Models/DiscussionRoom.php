<?php

namespace App\Models;

use App\Modules\Discussion\Models\DiscussionRoomTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionRoom extends Model
{
    use HasFactory, SoftDeletes;

    /** Targeting modes — mirrors virtual-classes / announcements. */
    public const TARGET_TYPES = [
        'all', 'students', 'teachers', 'parents', 'admins',
        'specific_users', 'specific_roles', 'job_titles',
    ];

    protected $fillable = [
        'school_id',
        'subject_id',
        'title',
        'description',
        'instructions',
        'category',
        'scope_type',
        'scope_id',
        'audience',
        'target_type',
        'grade_levels',
        'class_ids',
        'allow_topics',
        'allow_comments',
        'requires_approval',
        'status',
        'created_by',
        'topics_count',
        'comments_count',
        'last_activity_at',
    ];

    protected $casts = [
        'audience'          => 'array',
        'grade_levels'      => 'array',
        'class_ids'         => 'array',
        'scope_id'          => 'integer',
        'school_id'         => 'integer',
        'subject_id'        => 'integer',
        'created_by'        => 'integer',
        'allow_topics'      => 'boolean',
        'allow_comments'    => 'boolean',
        'requires_approval' => 'boolean',
        'last_activity_at'  => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(DiscussionRoomTarget::class, 'discussion_room_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(DiscussionTopic::class, 'room_id');
    }

    /**
     * Scope queries to a specific school.
     */
    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    // ── Targeting / visibility ────────────────────────────────────────────────

    /**
     * The ONE rule that decides whether a room is visible to a given user.
     * Both the member room list and the room-view gate derive from this so they
     * cannot drift. Copied 1:1 from VirtualClass::scopeVisibleTo (#234) — the
     * targeting vocabulary is identical.
     *
     * A parent inherits their children's grade/class context, so a room targeted
     * at a student's class also surfaces in the parent's account.
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

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
