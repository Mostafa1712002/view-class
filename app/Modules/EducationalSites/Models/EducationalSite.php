<?php

namespace App\Modules\EducationalSites\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * #270 — An external educational website link shown as a card.
 *
 * A NULL school_id means the site is global (shared across all schools).
 */
class EducationalSite extends Model
{
    use SoftDeletes;

    protected $table = 'educational_sites';

    protected $fillable = [
        'school_id', 'name_ar', 'name_en', 'description_ar', 'description_en',
        'url', 'logo_path', 'category', 'sort_order', 'opens_new_tab', 'is_active',
    ];

    protected $casts = [
        'sort_order'    => 'integer',
        'opens_new_tab' => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /** Best display name: Arabic if present, else English. */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_ar ?: $this->name_en;
    }

    /** Best display description: Arabic if present, else English. */
    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->description_ar ?: $this->description_en;
    }

    /** Public URL for the stored logo, or null when none was uploaded. */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
