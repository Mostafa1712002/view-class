<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryItemReaction extends Model
{
    protected $table = 'library_item_reactions';

    protected $fillable = [
        'library_item_id',
        'user_id',
        'type',
    ];

    public const TYPES = ['like', 'dislike', 'understood'];
}
