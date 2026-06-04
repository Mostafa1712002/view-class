<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CanteenProduct extends Model
{
    use SoftDeletes;

    protected $table = 'canteen_products';

    protected $fillable = [
        'canteen_id',
        'canteen_category_id',
        'name',
        'price',
        'calories',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'calories' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class, 'canteen_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CanteenCategory::class, 'canteen_category_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.ltrim($this->image_path, '/')) : null;
    }
}
