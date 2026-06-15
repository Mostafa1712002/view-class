<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateTemplate extends Model
{
    use SoftDeletes;

    /** appreciation=شكر, recognition=تقدير, general=عام, grades_notice=إشعار درجات */
    public const TYPES = ['appreciation', 'recognition', 'general', 'grades_notice'];
    public const ORIENTATIONS = ['landscape', 'portrait'];

    /** Placeholders the template body may reference. */
    public const PLACEHOLDERS = ['student_name', 'school', 'grade', 'date'];

    protected $fillable = [
        'school_id',
        'name',
        'type',
        'orientation',
        'background_path',
        'text_color',
        'name_color',
        'body',
        'created_by',
    ];

    protected $casts = [
        'body' => 'array',
    ];

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSchool($query, ?int $schoolId)
    {
        return $query->when($schoolId, fn ($w) => $w->where('school_id', $schoolId));
    }
}
