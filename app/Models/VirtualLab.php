<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualLab extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'virtual_labs';

    protected $fillable = [
        'category_id',
        'title',
        'description',
        'thumbnail_path',
        'external_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(VirtualLabCategory::class, 'category_id');
    }
}
