<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'library_items';

    protected $fillable = [
        'library_id',
        'school_id',
        'title',
        'description',
        'content_type',
        'file_path',
        'external_url',
        'thumbnail_path',
        'subject_id',
        'teacher_id',
        'tags',
        'sort_order',
        'is_public',
        'allow_comments',
        'created_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'allow_comments' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPES = ['video', 'pdf', 'image', 'presentation', 'link', 'other'];

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function ratings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LibraryItemRating::class, 'library_item_id');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LibraryItemComment::class, 'library_item_id')->latest();
    }

    /** Average rating (0 when none), rounded to 1 decimal. */
    public function averageRating(): float
    {
        return round((float) $this->ratings()->avg('rating'), 1);
    }

    public function ratingsCount(): int
    {
        return $this->ratings()->count();
    }
}
