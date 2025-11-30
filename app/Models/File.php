<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = [
        'school_id',
        'uploaded_by',
        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'type',
        'subject_id',
        'class_id',
        'academic_year_id',
        'description',
        'is_public',
        'download_count',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'size' => 'integer',
        'download_count' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'material' => 'مادة تعليمية',
            'assignment' => 'واجب',
            'resource' => 'مرجع',
            default => 'أخرى',
        };
    }

    public function getIconAttribute(): string
    {
        $extension = pathinfo($this->original_name, PATHINFO_EXTENSION);

        return match (strtolower($extension)) {
            'pdf' => 'la-file-pdf text-danger',
            'doc', 'docx' => 'la-file-word text-primary',
            'xls', 'xlsx' => 'la-file-excel text-success',
            'ppt', 'pptx' => 'la-file-powerpoint text-warning',
            'jpg', 'jpeg', 'png', 'gif' => 'la-file-image text-info',
            'mp4', 'avi', 'mov' => 'la-file-video text-purple',
            'mp3', 'wav' => 'la-file-audio text-pink',
            'zip', 'rar', '7z' => 'la-file-archive text-secondary',
            default => 'la-file text-muted',
        };
    }

    public function incrementDownloads(): void
    {
        $this->increment('download_count');
    }
}
