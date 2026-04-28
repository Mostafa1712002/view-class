<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectLesson extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_id',
        'name_ar',
        'name_en',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(SubjectUnit::class, 'unit_id');
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
