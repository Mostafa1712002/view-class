<?php

namespace App\Modules\SchoolBranches\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolBranch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_branches';

    protected $fillable = [
        'name_ar',
        'name_en',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function schools(): HasMany
    {
        return $this->hasMany(School::class, 'branch_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'en'
            ? ($this->name_en ?: $this->name_ar)
            : ($this->name_ar ?: $this->name_en);
    }
}
