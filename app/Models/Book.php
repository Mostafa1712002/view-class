<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'allow_download',
        'created_by',
    ];

    protected $casts = [
        'is_ministry' => 'boolean',
        'is_active' => 'boolean',
        'allow_download' => 'boolean',
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
        // Book PDFs live on the private disk; staff open them through the
        // permission-checked manage.books.file route, never a /storage URL.
        return $this->file_path && $this->id
            ? route('manage.books.file', $this->id)
            : null;
    }

    /**
     * Disk currently holding the PDF: the private `local` disk, or `public`
     * for files not yet moved by `books:move-to-private`.
     * ponytail: drop the public fallback once the command has run on every server.
     */
    public function fileDisk(): ?string
    {
        if ($this->source !== self::SOURCE_FILE || ! $this->file_path) {
            return null;
        }
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($this->file_path)) {
                return $disk;
            }
        }

        return null;
    }

    /** Remove the PDF from whichever disk holds it. */
    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk('local')->delete($this->file_path);
            Storage::disk('public')->delete($this->file_path);
        }
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path
            ? asset('storage/' . ltrim($this->cover_path, '/'))
            : null;
    }
}
