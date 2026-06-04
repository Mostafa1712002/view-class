<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CanteenCategory extends Model
{
    use SoftDeletes;

    protected $table = 'canteen_categories';

    protected $fillable = [
        'canteen_id',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class, 'canteen_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(CanteenProduct::class, 'canteen_category_id');
    }
}
