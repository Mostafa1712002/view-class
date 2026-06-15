<?php

namespace App\Modules\QuestionBankCore\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Educational compound (المجمع) — groups schools (#248).
 */
class Compound extends Model
{
    use SoftDeletes;

    protected $table = 'compounds';

    protected $fillable = [
        'educational_company_id',
        'name_ar',
        'name_en',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'compound_school', 'compound_id', 'school_id')
            ->withTimestamps();
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'en'
            ? ($this->name_en ?: $this->name_ar)
            : ($this->name_ar ?: $this->name_en);
    }
}
