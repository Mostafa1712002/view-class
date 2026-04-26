<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'educational_company_id',
        'name',
        'name_ar',
        'name_en',
        'branch',
        'sort_order',
        'educational_track',
        'stage',
        'city',
        'default_language',
        'code',
        'logo',
        'cover_image',
        'address',
        'phone',
        'fax',
        'email',
        'website',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function educationalCompany(): BelongsTo
    {
        return $this->belongsTo(EducationalCompany::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function classes(): HasManyThrough
    {
        return $this->hasManyThrough(ClassRoom::class, Section::class);
    }

    public function currentAcademicYear(): ?AcademicYear
    {
        return $this->academicYears()->where('is_current', true)->first();
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
}
