<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use SoftDeletes;

    public const STATUSES   = ['draft', 'published', 'closed'];
    public const AUDIENCES  = ['all', 'students', 'parents', 'teachers'];

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'status',
        'audience',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at'   => 'date',
    ];

    // ──────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeForSchool($query, ?int $schoolId)
    {
        return $query->when($schoolId, fn ($w) => $w->where('school_id', $schoolId));
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    public function isOpen(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }
        $today = now()->toDateString();
        if ($this->starts_at && $this->starts_at->toDateString() > $today) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->toDateString() < $today) {
            return false;
        }
        return true;
    }

    public function isForAudience(User $user): bool
    {
        if ($this->audience === 'all') {
            return true;
        }
        return match ($this->audience) {
            'students' => $user->isStudent(),
            'parents'  => $user->isParent(),
            'teachers' => $user->isTeacher(),
            default    => false,
        };
    }
}
