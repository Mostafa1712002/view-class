<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryItemComment extends Model
{
    use SoftDeletes;

    protected $table = 'library_item_comments';

    protected $fillable = ['library_item_id', 'user_id', 'body'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(LibraryItem::class, 'library_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
