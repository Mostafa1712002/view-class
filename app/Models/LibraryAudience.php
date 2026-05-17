<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryAudience extends Model
{
    use HasFactory;

    protected $table = 'library_audiences';

    protected $fillable = [
        'library_id',
        'audience_type',
        'audience_id',
    ];

    public const TYPES = ['school', 'grade', 'class', 'user', 'teacher'];

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }
}
