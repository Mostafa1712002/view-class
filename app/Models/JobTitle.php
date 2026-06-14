<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTitle extends Model
{
    protected $fillable = [
        'school_id',
        'slug',
        'name_ar',
        'name_en',
        'is_active',
        'sort_order',
        'description',
        'role_id',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** The primary role linked to this job title (optional). */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Permissions configured for this job title.
     * The pivot carries: scope (enum), timestamps.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'job_title_permissions',
            'job_title_id',
            'permission_id'
        )->withPivot('scope')->withTimestamps();
    }

    /** Users currently assigned this job title. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForSchool(Builder $q, ?int $schoolId): Builder
    {
        return $q->where(function ($w) use ($schoolId) {
            $w->whereNull('school_id');
            if ($schoolId) {
                $w->orWhere('school_id', $schoolId);
            }
        });
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Whether this job title has ANY permissions configured.
     * Used for the default-allow rule: if false → user sees everything.
     */
    public function hasAnyPermissions(): bool
    {
        return $this->permissions()->exists();
    }

    /** Check a specific permission slug against this job title. */
    public function hasPermission(string $slug): bool
    {
        return $this->permissions()->where('slug', $slug)->exists();
    }
}
