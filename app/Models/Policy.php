<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Policy extends Model
{
    use SoftDeletes;

    protected $table = 'policies';

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'target_roles',
        'is_active',
        'file_path',
        'external_url',
        'created_by',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'is_active' => 'boolean',
    ];

    /** Roles a policy can target. */
    public const ROLES = ['student', 'teacher', 'parent', 'school-admin', 'super-admin'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(PolicyAcknowledgement::class);
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? asset('storage/'.ltrim($this->file_path, '/')) : null;
    }
}
