<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    public const SOURCE_FILE = 'file';
    public const SOURCE_EXTERNAL = 'external_url';

    protected $fillable = [
        'school_id',
        'subject_id',
        'grade_level',
        'academic_term_id',
        'title',
        'description',
        'source',
        'file_path',
        'external_url',
        'cover_path',
        'is_ministry',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_ministry' => 'boolean',
        'is_active' => 'boolean',
        'grade_level' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getReadUrlAttribute(): ?string
    {
        if ($this->source === self::SOURCE_EXTERNAL) {
            return $this->external_url;
        }
        return $this->file_path
            ? asset('storage/' . ltrim($this->file_path, '/'))
            : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path
            ? asset('storage/' . ltrim($this->cover_path, '/'))
            : null;
    }
}
