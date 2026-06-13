<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class SubjectContent extends Model
{
    use SoftDeletes;

    public const TYPE_VIDEO      = 'video';
    public const TYPE_ATTACHMENT = 'attachment';
    public const TYPE_LINK       = 'link';

    public const TYPES = [
        self::TYPE_VIDEO      => 'فيديو',
        self::TYPE_ATTACHMENT => 'مرفق',
        self::TYPE_LINK       => 'رابط',
    ];

    protected $fillable = [
        'school_id',
        'subject_id',
        'teacher_id',
        'type',
        'title',
        'description',
        'url',
        'file_path',
        'is_published',
        'available_from',
        'available_until',
        'views_count',
    ];

    protected $casts = [
        'is_published'   => 'boolean',
        'available_from' => 'date',
        'available_until'=> 'date',
        'views_count'    => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /** Only published records. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Available now: available_from is null or in the past AND
     * available_until is null or in the future.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(function ($w) use ($today) {
            $w->whereNull('available_from')
              ->orWhereDate('available_from', '<=', $today);
        })->where(function ($w) use ($today) {
            $w->whereNull('available_until')
              ->orWhereDate('available_until', '>=', $today);
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
