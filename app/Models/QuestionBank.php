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
        'is_library',
        'created_by',
    ];

    protected $casts = [
        'is_library' => 'boolean',
    ];

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
