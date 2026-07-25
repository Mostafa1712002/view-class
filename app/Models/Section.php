<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Promotion\Support\StandardGrades;

class Section extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'school_id',
        'gender',
        'level',
        'grade_number',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'grade_number' => 'integer',
    ];

    /** Short standard label for this grade's ordinal, or null if unmapped. */
    public function getStandardLabelAttribute(): ?string
    {
        return $this->grade_number ? StandardGrades::label($this->grade_number) : null;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teachers(): HasMany
    {
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('slug', 'teacher');
        });
    }

    public function students(): HasMany
    {
        return $this->users()->whereHas('roles', function ($query) {
            $query->where('slug', 'student');
        });
    }

    public function getGenderLabelAttribute(): string
    {
        return match($this->gender) {
            'male' => 'بنين',
            'female' => 'بنات',
            default => $this->gender,
        };
    }

    public function getLevelLabelAttribute(): string
    {
        return match($this->level) {
            'primary' => 'ابتدائي',
            'intermediate' => 'متوسط',
            'secondary' => 'ثانوي',
            default => $this->level,
        };
    }
}
