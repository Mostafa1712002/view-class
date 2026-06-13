<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternalMail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'sender_id',
        'subject',
        'importance',
        'body',
        'attachment_path',
        'related_student_id',
        'is_draft',
    ];

    protected function casts(): array
    {
        return [
            'is_draft'   => 'boolean',
            'importance' => 'string',
        ];
    }

    public const IMPORTANCES = ['normal', 'important', 'urgent'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(InternalMailRecipient::class, 'mail_id');
    }

    public function relatedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_student_id');
    }
}
