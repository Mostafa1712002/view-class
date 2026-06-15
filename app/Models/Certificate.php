<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    use SoftDeletes;

    public const TYPES = ['student', 'teacher', 'training', 'appreciation'];
    public const STATUSES = ['draft', 'published'];

    protected $fillable = [
        'school_id',
        'type',
        'template_id',
        'title',
        'recipient_user_id',
        'issued_by',
        'issue_date',
        'status',
        'note',
        'file_path',
        'share_token',
        'progress',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'progress'   => 'integer',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForSchool($query, ?int $schoolId)
    {
        return $query->when($schoolId, fn ($w) => $w->where('school_id', $schoolId));
    }
}
