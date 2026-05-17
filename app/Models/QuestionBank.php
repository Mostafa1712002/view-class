<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionBank extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'name_ar',
        'name_en',
        'description',
        'is_library',
        'visibility',
        'status',
        'source',
        'grade_level',
        'category_type',
        'is_ana_qudurat_linkable',
        'external_id',
        'link_status',
        'last_sync_at',
        'created_by',
    ];

    protected $casts = [
        'is_library' => 'boolean',
        'is_ana_qudurat_linkable' => 'boolean',
        'grade_level' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ARCHIVED = 'archived';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_LIBRARY = 'library';
    public const SOURCE_IMPORT = 'import';
    public const SOURCE_ANA_QUDURAT = 'ana_qudurat';

    public const CATEGORY_SCHOOL = 'school';
    public const CATEGORY_QUDURAT = 'qudurat';
    public const CATEGORY_VERBAL = 'verbal';
    public const CATEGORY_QUANTITATIVE = 'quantitative';
    public const CATEGORY_SPEED_READING = 'speed_reading';

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'question_bank_subjects');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'question_bank_users')->withPivot('role');
    }

    public function viewers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'viewer');
    }

    public function editors(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'editor');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(BankQuestion::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'en' && ! empty($this->name_en)) {
            return $this->name_en;
        }
        return $this->name_ar;
    }
}
