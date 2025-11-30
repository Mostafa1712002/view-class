<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'logo',
        'address',
        'phone',
        'email',
        'website',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

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
