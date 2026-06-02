<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryItemRating extends Model
{
    protected $table = 'library_item_ratings';

    protected $fillable = ['library_item_id', 'user_id', 'rating'];

    protected $casts = ['rating' => 'integer'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class, 'library_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
